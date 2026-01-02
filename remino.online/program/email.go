package main

import (
	"bytes"
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

/* =========================================
   KONFIGURASI
========================================= */
const (
	DB_USER = "remino_admin"
	DB_PASS = "REMINOictgroup2025"
	DB_HOST = "127.0.0.1:3306"
	DB_NAME = "remino_db"

	API_KEY      = "re_cKRxPxTp_77hKpbVU8HU1Dp12GM9UZ61Q"
	SENDER_EMAIL = "no-reply@remino.online"

	SERVER_ADDR = ":8080"
)
/* =========================================
   KONEKSI DATABASE
========================================= */
func connectDB() (*sql.DB, error) {
	// timeout koneksi supaya tidak hang
	dsn := fmt.Sprintf("%s:%s@tcp(%s)/%s?parseTime=true&timeout=5s&readTimeout=5s&writeTimeout=5s",
		DB_USER, DB_PASS, DB_HOST, DB_NAME)

	db, err := sql.Open("mysql", dsn)
	if err != nil {
		return nil, err
	}

	// validasi koneksi
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		_ = db.Close()
		return nil, err
	}

	return db, nil
}

/* =========================================
   STRUCT REQUEST DARI PHP
========================================= */
type UserEmailRequest struct {
	Email   string `json:"email"`
	Subject string `json:"subject"`
	Message string `json:"message"`
}

/* =========================================
   STRUCT UNTUK RESEND API
========================================= */
type ResendEmailRequest struct {
	From    string   `json:"from"`
	To      []string `json:"to"`
	Subject string   `json:"subject"`
	HTML    string   `json:"html"`
}

/* =========================================
   KIRIM EMAIL PAKAI RESEND
========================================= */
func sendEmail(to string, subject string, html string) error {
	body := ResendEmailRequest{
		From:    fmt.Sprintf("REMINO Notification <%s>", SENDER_EMAIL),
		To:      []string{to},
		Subject: subject,
		HTML:    html,
	}

	jsonBody, err := json.Marshal(body)
	if err != nil {
		return err
	}

	req, err := http.NewRequest("POST", "https://api.resend.com/emails", bytes.NewBuffer(jsonBody))
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

	fmt.Println("=====================================")
	fmt.Println("RESEND STATUS :", resp.Status)
	fmt.Println("RESEND BODY   :", string(respBody))
	fmt.Println("=====================================")

	if resp.StatusCode >= 300 {
		return fmt.Errorf("Resend API error: %s | Detail: %s", resp.Status, string(respBody))
	}

	return nil
}

/* =========================================
   SCHEDULER: CEK TASK YANG SIAP DIKIRIM
   - Wajib sesuai jadwal: date_time <= NOW()
   - Ambil pending, lock jadi processing_email
   - Kalau sukses: status -> partial (untuk whatsapp.go)
========================================= */
func processScheduledTasks() {
	db, err := connectDB()
	if err != nil {
		fmt.Println("DB ERROR:", err)
		return
	}
	defer db.Close()

	rows, err := db.Query(`
		SELECT t.ID, c.GMAIL, t.task, t.message
		FROM task t
		JOIN contact c ON t.contact_id = c.ID
		WHERE t.status='pending'
		  AND t.date_time <= NOW()
		ORDER BY t.date_time ASC
		LIMIT 50
	`)
	if err != nil {
		fmt.Println("QUERY ERROR:", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var id int
		var email, subject, message string

		if err := rows.Scan(&id, &email, &subject, &message); err != nil {
			fmt.Println("SCAN ERROR:", err)
			continue
		}

		// Kirim email
		html := fmt.Sprintf("<h3>%s</h3><p>%s</p>", subject, message)
		if err := sendEmail(email, subject, html); err != nil {
			fmt.Println("GAGAL KIRIM EMAIL TASK:", id, err)
			continue
		}

		// Email sukses -> partial (biar whatsapp.go lanjut)
		_, err := db.Exec(`UPDATE task SET status='partial' WHERE ID=? AND status='pending'`, id)
		if err != nil {
			fmt.Println("UPDATE PARTIAL ERROR:", id, err)
			continue
		}

		fmt.Println("Task ID", id, "email terkirim ke", email, "status -> partial")
	}

	if err := rows.Err(); err != nil {
		fmt.Println("ROWS ERROR:", err)
	}
}

/* =========================================
   MAIN APP
========================================= */
func main() {
	// Scheduler
	go func() {
		for {
			fmt.Println("[SCHEDULER] Checking tasks...")
			processScheduledTasks()
			time.Sleep(30 * time.Second)
		}
	}()

	// Endpoint untuk PHP (kirim email langsung, tidak masuk DB)
	http.HandleFunc("/send-message", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusOK)
			return
		}
		if r.Method != "POST" {
			http.Error(w, "Gunakan POST method", http.StatusBadRequest)
			return
		}

		var reqData UserEmailRequest
		if err := json.NewDecoder(r.Body).Decode(&reqData); err != nil {
			http.Error(w, "Input JSON tidak valid", http.StatusBadRequest)
			return
		}

		if reqData.Email == "" || reqData.Subject == "" || reqData.Message == "" {
			http.Error(w, "Email, subject, message wajib diisi", http.StatusBadRequest)
			return
		}

		html := fmt.Sprintf("<h3>%s</h3><p>%s</p>", reqData.Subject, reqData.Message)
		if err := sendEmail(reqData.Email, reqData.Subject, html); err != nil {
			http.Error(w, "Gagal mengirim email: "+err.Error(), http.StatusInternalServerError)
			return
		}

		w.Write([]byte("Pesan berhasil dikirim ke " + reqData.Email))
	})

	fmt.Println("=====================================")
	fmt.Println("REMINO EMAIL SERVER berjalan di http://localhost:8081")
	fmt.Println("Scheduler aktif setiap 30 detik (pending + date_time <= NOW() -> partial)")
	fmt.Println("=====================================")

	log.Fatal(http.ListenAndServe(":8081", nil))
}
