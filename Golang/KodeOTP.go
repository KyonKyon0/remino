package main

import (
	"bytes"
	"crypto/rand"
	"encoding/json"
	"fmt"
	"log"
	"math/big"
	"net/http"
)

// =============================
// Generate OTP 6 digit
// =============================
func generateOTP() string {
	max := big.NewInt(1000000)
	n, _ := rand.Int(rand.Reader, max)
	return fmt.Sprintf("%06d", n.Int64())
}

// =============================
// Struct untuk request ke Resend
// =============================
type ResendEmailRequest struct {
	From    string   `json:"from"`
	To      []string `json:"to"`
	Subject string   `json:"subject"`
	HTML    string   `json:"html"`
}

func sendEmailResend(to string, subject string, html string) error {

	apiKey := "re_5JK3SmJT_8HrWAdnKm2s6bRet8RBs7LJ1" // <-- GANTI DENGAN API KEY RESEND ANDA

	body := ResendEmailRequest{
		From:    "Remino <onboarding@resend.dev>", // wajib pakai ini kalau belum verifikasi domain
		To:      []string{to},
		Subject: subject,
		HTML:    html,
	}

	jsonBody, _ := json.Marshal(body)

	req, err := http.NewRequest("POST", "https://api.resend.com/emails", bytes.NewBuffer(jsonBody))
	if err != nil {
		return err
	}

	req.Header.Set("Authorization", "Bearer "+apiKey)
	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	// Jika status bukan 200/201 → ERROR
	if resp.StatusCode >= 300 {
		return fmt.Errorf("resend API error: %s", resp.Status)
	}

	return nil
}

// =============================
// HTTP Handler
// =============================
func main() {

	http.HandleFunc("/send-otp", func(w http.ResponseWriter, r *http.Request) {

		// ==== FIX CORS ====
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusOK)
			return
		}
		// ====================

		email := r.URL.Query().Get("email")
		if email == "" {
			http.Error(w, "Email wajib diisi", http.StatusBadRequest)
			return
		}

		otp := generateOTP()

		html := fmt.Sprintf("<h2>Kode OTP Anda</h2><p><b>%s</b></p>", otp)

		err := sendEmailResend(email, "Kode OTP Anda", html)
		if err != nil {
			http.Error(w, "Gagal mengirim OTP: "+err.Error(), 500)
			return
		}

		w.Write([]byte("OTP berhasil dikirim ke " + email))
	})

	fmt.Println("Server berjalan di http://localhost:8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}

