package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
)

// =============================
// Struct Request dari Frontend
// =============================
type UserEmailRequest struct {
	Email   string `json:"email"`
	Subject string `json:"subject"`
	Message string `json:"message"`
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

	apiKey := "re_5JK3SmJT_8HrWAdnKm2s6bRet8RBs7LJ1" // <-- ganti API KEY

	body := ResendEmailRequest{
		From:    "Remino <onboarding@resend.dev>",
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

	if resp.StatusCode >= 300 {
		return fmt.Errorf("Resend API error: %s", resp.Status)
	}

	return nil
}

// =============================
// HTTP Handler
// =============================
func main() {

	http.HandleFunc("/send-message", func(w http.ResponseWriter, r *http.Request) {

		// ==== CORS ====
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
		w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS")

		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusOK)
			return
		}

		if r.Method != "POST" {
			http.Error(w, "Gunakan POST", http.StatusBadRequest)
			return
		}

		// ==== Baca JSON dari frontend ====
		var reqData UserEmailRequest
		err := json.NewDecoder(r.Body).Decode(&reqData)
		if err != nil {
			http.Error(w, "Input tidak valid", 400)
			return
		}

		if reqData.Email == "" || reqData.Subject == "" || reqData.Message == "" {
			http.Error(w, "Email, subject, dan message wajib diisi", 400)
			return
		}

		// ==== Kirim email ====
		html := fmt.Sprintf("<h3>%s</h3><p>%s</p>", reqData.Subject, reqData.Message)

		err = sendEmailResend(reqData.Email, reqData.Subject, html)
		if err != nil {
			http.Error(w, "Gagal mengirim: "+err.Error(), 500)
			return
		}

		w.Write([]byte("Pesan berhasil dikirim ke " + reqData.Email))
	})

	fmt.Println("Server berjalan di http://localhost:8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}
