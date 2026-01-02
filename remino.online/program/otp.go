package main

import (
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"strings"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

/* ============================
   KONFIGURASI
============================ */
const (
	DB_USER = "remino_admin"
	DB_PASS = "REMINOictgroup2025"
	DB_HOST = "127.0.0.1:3306"
	DB_NAME = "remino_db"

	API_KEY      = "re_Gd6tNDhq_G6Yzzhx7AyBzAGNgBskU3F9J"
	SENDER_EMAIL = "no-reply@remino.online"

	SERVER_ADDR = ":8081"
)

/* ============================
   STRUCT
============================ */
type SendTaskRequest struct {
	TaskID int `json:"task_id"`
}

type ResendPayload struct {
	From    string   `json:"from"`
	To      []string `json:"to"`
	Subject string   `json:"subject"`
	HTML    string   `json:"html"`
}

/* ============================
   DB CONNECTION
============================ */
func connectDB() (*sql.DB, error) {
	dsn := fmt.Sprintf(
		"%s:%s@tcp(%s)/%s?parseTime=true&charset=utf8mb4&collation=utf8mb4_general_ci",
		DB_USER, DB_PASS, DB_HOST, DB_NAME,
	)
	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, err
	}

	// Pastikan koneksi valid (bukan cuma open)
	db.SetConnMaxLifetime(2 * time.Minute)
	db.SetMaxOpenConns(10)
	db.SetMaxIdleConns(5)

	if err := db.Ping(); err != nil {
		db.Close()
		return nil, err
	}

	return db, nil
}

/* ============================
   SEND EMAIL (RESEND)
============================ */
func sendEmail(to, subject, html string) error {
	payload := ResendPayload{
		From:    fmt.Sprintf("REMINO Notification <%s>", SENDER_EMAIL),
		To:      []string{to},
		Subject: subject,
		HTML:    html,
	}

	body, _ := json.Marshal(payload)

	req, err := http.NewRequest(
		"POST",
		"https://api.resend.com/emails",
		bytes.NewBuffer(body),
	)
	if err != nil {
		return err
	}

	req.Header.Set("Authorization", "Bearer "+API_KEY)
	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: 15 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	respBody, _ := io.ReadAll(resp.Body)

	log.Println("RESEND STATUS:", resp.Status)
	log.Println("RESEND BODY:", string(respBody))

	if resp.StatusCode >= 300 {
		return fmt.Errorf("resend error: %s", string(respBody))
	}

	return nil
}

/* ============================
   HANDLER /send-task
============================ */
func handleSendTask(w http.ResponseWriter, r *http.Request) {
	log.Println("HIT /send-task")

	if r.Method != http.MethodPost {
		http.Error(w, "POST only", http.StatusMethodNotAllowed)
		return
	}

	var req SendTaskRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.TaskID <= 0 {
		http.Error(w, "Invalid JSON", http.StatusBadRequest)
		return
	}

	db, err := connectDB()
	if err != nil {
		log.Println("DB CONNECT ERROR:", err)
		http.Error(w, "DB error", 500)
		return
	}
	defer db.Close()

	/* ====== FORENSIK LOG (INI YANG BIKIN PASTI KELAR) ====== */
	var dbName, host string
	_ = db.QueryRow("SELECT DATABASE()").Scan(&dbName)
	_ = db.QueryRow("SELECT @@hostname").Scan(&host)

	var cnt int
	_ = db.QueryRow("SELECT COUNT(*) FROM task").Scan(&cnt)

	log.Println("CONNECTED DB:", dbName, "HOST:", host)
	log.Println("TOTAL TASK ROWS:", cnt)
	log.Println("REQUEST TASK_ID:", req.TaskID)

	// Cek cepat apakah ID ada di DB yang sedang diakses
	var exists int
	_ = db.QueryRow("SELECT COUNT(*) FROM task WHERE id = ?", req.TaskID).Scan(&exists)
	log.Println("TASK_ID EXISTS COUNT:", exists)

	/* ====================================================== */

	var (
		email   string
		subject string
		message string
		status  string
	)

	// LEFT JOIN agar task tetap ketemu walau contact rusak
	err = db.QueryRow(`
		SELECT 
			IFNULL(c.GMAIL, ''),
			IFNULL(t.task, ''),
			IFNULL(t.message, ''),
			IFNULL(t.status, '')
		FROM task t
		LEFT JOIN contact c ON t.contact_id = c.ID
		WHERE t.id = ?
	`, req.TaskID).Scan(&email, &subject, &message, &status)

	if err == sql.ErrNoRows {
		log.Println("TASK NOT FOUND IN THIS DB:", req.TaskID)
		http.Error(w, "Task tidak ditemukan", 404)
		return
	}
	if err != nil {
		log.Println("QUERY ERROR:", err)
		http.Error(w, "Query error", 500)
		return
	}

	// Kalau sudah sent, tidak kirim lagi
	if strings.ToLower(status) == "sent" {
		w.Write([]byte("Task sudah dikirim"))
		return
	}

	email = strings.TrimSpace(email)
	if email == "" || !strings.Contains(email, "@") {
		log.Println("EMAIL INVALID FOR TASK:", req.TaskID, "EMAIL:", email)
		_, _ = db.Exec("UPDATE task SET status='failed' WHERE id=?", req.TaskID)
		http.Error(w, "Email contact kosong / tidak valid", 400)
		return
	}

	html := fmt.Sprintf("<h3>%s</h3><p>%s</p>", subject, message)

	if err := sendEmail(email, subject, html); err != nil {
		log.Println("SEND EMAIL FAILED:", err)
		_, _ = db.Exec("UPDATE task SET status='failed' WHERE id=?", req.TaskID)
		http.Error(w, "Gagal kirim email", 500)
		return
	}

	_, err = db.Exec("UPDATE task SET status='sent' WHERE id=?", req.TaskID)
	if err != nil {
		log.Println("UPDATE STATUS ERROR:", err)
		http.Error(w, "Update status gagal", 500)
		return
	}

	log.Println("TASK SENT SUCCESS:", req.TaskID)
	w.Write([]byte("EMAIL TERKIRIM & STATUS SENT"))
}

/* ============================
   MAIN
============================ */
func main() {
	log.Println("=====================================")
	log.Println("REMINO SEND TASK EMAIL SERVICE RUNNING")
	log.Println("PORT", SERVER_ADDR)
	log.Println("DB HOST", DB_HOST, "DB NAME", DB_NAME)
	log.Println("=====================================")

	http.HandleFunc("/send-task", handleSendTask)

	log.Fatal(http.ListenAndServe(SERVER_ADDR, nil))
}
