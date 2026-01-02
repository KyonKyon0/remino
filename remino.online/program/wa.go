package main

import (
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"math/rand"
	"net/http"
	"time"

	_ "github.com/go-sql-driver/mysql"
)

/*
	=========================================
	  KONFIGURASI

=========================================
*/
const (
	DB_USER = "remino_admin"
	DB_PASS = "REMINOictgroup2025"
	DB_HOST = "127.0.0.1:3306"
	DB_NAME = "remino_db"

	// PERINGATAN: Jangan sebarkan token ini ke publik/internet!
	WA_API_TOKEN = "8K8AW6nVUNe5acbwVhpD"
	WA_API_URL   = "https://api.fonnte.com/send"
	SERVER_ADDR = ":8082"
)

/*
	=========================================
	  KONEKSI DATABASE

=========================================
*/
func connectDB() (*sql.DB, error) {
	dsn := fmt.Sprintf("%s:%s@tcp(%s)/%s?parseTime=true",
		DB_USER, DB_PASS, DB_HOST, DB_NAME)
	return sql.Open("mysql", dsn)
}

/*
	=========================================
	  STRUCT REQUEST DARI PHP/CLIENT

=========================================
*/
type UserWARequest struct {
	Phone   string `json:"phone"`
	Message string `json:"message"`
}

/*
	=========================================
	  STRUCT UNTUK FONNTE API

=========================================
*/
type FonnteRequest struct {
	Target  string `json:"target"`
	Message string `json:"message"`
}

/*
	=========================================
	  KIRIM WA PAKAI FONNTE

=========================================
*/
func sendWhatsApp(target string, message string) error {
	body := FonnteRequest{
		Target:  target,
		Message: message,
	}

	jsonBody, _ := json.Marshal(body)

	req, err := http.NewRequest("POST", WA_API_URL, bytes.NewBuffer(jsonBody))
	if err != nil {
		return err
	}

	req.Header.Set("Authorization", WA_API_TOKEN)
	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	// Cek status code, kalau bukan 200 anggap error
	if resp.StatusCode >= 300 {
		respBody, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("WA API Error: %s | Detail: %s", resp.Status, string(respBody))
	}

	return nil
}

/*
	=========================================
	  SCHEDULER: CEK TASK YANG SIAP DIKIRIM

=========================================
*/
func processScheduledTasks() {
	db, err := connectDB()
	if err != nil {
		fmt.Println("DB ERROR:", err)
		return
	}
	defer db.Close()

	rows, err := db.Query(`
    SELECT t.id, c.TELEPON, t.task, t.message
    FROM task t
    JOIN contact c ON t.contact_id = c.ID
    WHERE t.status='partial'
    AND t.date_time <= NOW()
    `)

	if err != nil {
		fmt.Println("QUERY ERROR:", err)
		return
	}
	defer rows.Close()

	// Counter untuk melihat berapa pesan terkirim dalam satu batch
	countSent := 0

	for rows.Next() {
		var id int
		var phone, taskTitle, messageRaw string

		err := rows.Scan(&id, &phone, &taskTitle, &messageRaw)
		if err != nil {
			fmt.Println("SCAN ERROR:", err)
			continue
		}

		fullMessage := fmt.Sprintf("Hallo! Ada pengingat untuk kamu nih 😊\n\n*%s*\n%s\n\nBalas Agar Tidak Dibanned\nPesan Otomatis Dari Remino System", taskTitle, messageRaw)

		err = sendWhatsApp(phone, fullMessage)
		if err != nil {
			fmt.Println("GAGAL KIRIM TASK ID:", id, err)
			continue
		}

		_, _ = db.Exec("UPDATE task SET status='sent' WHERE id=?", id)
		fmt.Println("✅ Task ID", id, "telah dikirim ke WA:", phone)

		countSent++

		// ============================================================
		// PERUBAHAN 2: MENAMBAHKAN RANDOM DELAY (JEDA ACAK)
		// ============================================================
		// Kita generate angka acak antara 5 sampai 15 detik
		minDelay := 5
		maxDelay := 15
		randomSeconds := rand.Intn(maxDelay-minDelay+1) + minDelay

		fmt.Printf("⏳ Menunggu %d detik sebelum mengirim pesan berikutnya agar aman...\n", randomSeconds)
		time.Sleep(time.Duration(randomSeconds) * time.Second)
		// ============================================================
	}

	if countSent == 0 {
		// Tidak perlu print apa-apa kalau tidak ada yang dikirim biar log bersih
	} else {
		fmt.Println("Batch selesai. Total terkirim:", countSent)
	}
}

/*
	=========================================
	  MAIN APP

=========================================
*/
func main() {
	// Inisialisasi seed random agar angka benar-benar acak setiap program jalan
	// (Penting untuk Go versi lama, di Go versi baru opsional tapi bagus ada)
	rand.Seed(time.Now().UnixNano())

	go func() {
		for {
			fmt.Println("[SCHEDULER] Checking pending WhatsApp tasks...")
			processScheduledTasks()

			// Jeda antar pengecekan database (misal 1 menit)
			// Jangan terlalu cepat query DB nya
			time.Sleep(30 * time.Second)
		}
	}()

	http.HandleFunc("/send-wa", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusOK)
			return
		}

		if r.Method != "POST" {
			http.Error(w, "Gunakan POST method", 400)
			return
		}

		var reqData UserWARequest
		err := json.NewDecoder(r.Body).Decode(&reqData)
		if err != nil {
			http.Error(w, "Input JSON tidak valid", 400)
			return
		}

		if reqData.Phone == "" || reqData.Message == "" {
			http.Error(w, "Nomor Telepon dan Pesan wajib diisi", 400)
			return
		}

		err = sendWhatsApp(reqData.Phone, reqData.Message)
		if err != nil {
			http.Error(w, "Gagal mengirim WA: "+err.Error(), 500)
			return
		}

		w.Write([]byte("Pesan WhatsApp berhasil dikirim ke " + reqData.Phone))
	})

	fmt.Println("=====================================================")
	fmt.Println("REMINO WA SERVER berjalan di http://localhost:8082")
	fmt.Println("Scheduler aktif dengan JEDA ANTI-BANNED")
	fmt.Println("=====================================================")

	log.Fatal(http.ListenAndServe(":8082", nil))
}
