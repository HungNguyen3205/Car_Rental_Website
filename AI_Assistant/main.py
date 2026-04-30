import os
import json
import requests
import torch
import random
import traceback
import nltk
import sys
from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
from model import NeuralNet
from nltk_utils import bag_of_words, tokenize

# Load environment variables
load_dotenv(os.path.join(os.path.dirname(__file__), '../BE/.env'))

app = Flask(__name__)
CORS(app)

BE_URL = "http://127.0.0.1:8000/api"

def safe_print(msg):
    try:
        print(msg)
    except:
        try:
            print(msg.encode('utf-8', errors='ignore').decode('ascii', errors='ignore'))
        except:
            pass

# Ensure NLTK data is downloaded
try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')

# ======================
# AI MODEL LOADING
# ======================
device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

try:
    with open('intents.json', 'r', encoding='utf-8') as json_data:
        intents = json.load(json_data)

    FILE = "data.pth"
    if not os.path.exists(FILE):
        safe_print(f"ERROR: {FILE} not found! Please run train.py first.")
    
    data = torch.load(FILE, map_location=device)

    input_size = data["input_size"]
    hidden_size = data["hidden_size"]
    output_size = data["output_size"]
    all_words = data["all_words"]
    tags = data["tags"]
    model_state = data["model_state"]

    model = NeuralNet(input_size, hidden_size, output_size).to(device)
    model.load_state_dict(model_state)
    model.eval()
    safe_print("AI Model loaded successfully!")
except Exception as e:
    safe_print(f"ERROR loading AI model: {e}")

# ======================
# HELPER FUNCTIONS
# ======================

def parse_to_int(val):
    """Safely convert any currency string or float to a clean integer."""
    try:
        if val is None or val == "": return 0
        if isinstance(val, (int, float)):
            return int(val)
        # If string, handle dots/commas
        s_val = str(val)
        if "." in s_val and "," in s_val:
            # Format like 1.234.567,89 or 1,234,567.89
            # We assume the last one is decimal
            if s_val.rfind(".") > s_val.rfind(","):
                s_val = s_val.replace(",", "")
            else:
                s_val = s_val.replace(".", "").replace(",", ".")
        elif "." in s_val:
            # Check if it looks like "3.200.000" or "3200000.00"
            parts = s_val.split(".")
            if len(parts) > 2: # Multiple dots like 3.200.000
                s_val = s_val.replace(".", "")
            elif len(parts[1]) > 2: # Single dot but many digits like 3.20000
                s_val = s_val.replace(".", "")
            # otherwise it's likely a decimal point like 3200000.00
        elif "," in s_val:
            s_val = s_val.replace(",", "")
            
        return int(float(s_val))
    except Exception as e:
        safe_print(f"Parse Error for {val}: {e}")
        return 0

def format_price_display(val):
    clean_val = parse_to_int(val)
    return f"{clean_val:,}".replace(",", ".")

def get_payment_qr_card(booking):
    try:
        ma_don = booking.get("ma_dat_xe", "N/A")
        raw_tong_tien = booking.get("tong_tien", 0)
        car_name = booking.get("car_name") or booking.get("name") or "Xe VinFast"
        start = booking.get("start_date", "N/A")
        end = booking.get("end_date", "N/A")
        
        clean_amount = parse_to_int(raw_tong_tien)
        qr_url = f"https://img.vietqr.io/image/970422-0971120038-qr_only.png?amount={clean_amount}&addInfo={ma_don}"
        
        display_price = format_price_display(raw_tong_tien)
        
        return f"""
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; padding: 20px; color: white; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div style="font-weight: 800; font-size: 1.1rem; margin-bottom: 5px; color: #60a5fa;">📄 CHI TIẾT HÓA ĐƠN</div>
            <div style="font-size: 0.8rem; opacity: 0.7; margin-bottom: 15px;">Mã đơn: {ma_don}</div>
            
            <div style="text-align: left; background: rgba(255,255,255,0.05); padding: 12px; border-radius: 10px; margin-bottom: 15px; font-size: 0.85rem;">
                <div style="margin-bottom: 5px;">🚗 <b>Phương tiện:</b> {car_name}</div>
                <div style="margin-bottom: 5px;">📅 <b>Nhận xe:</b> {start}</div>
                <div style="margin-bottom: 5px;">📅 <b>Trả xe:</b> {end}</div>
                <div style="margin-bottom: 5px;">📍 <b>Nơi nhận:</b> {booking.get('dia_chi_nhan_xe', 'Showroom')}</div>
                <div>🏁 <b>Nơi trả:</b> {booking.get('dia_chi_tra_xe', 'Showroom')}</div>
            </div>

            <div style="background: white; border-radius: 12px; padding: 10px; display: inline-block; margin-bottom: 15px;">
                <img src="{qr_url}" style="width: 160px; height: 160px;">
            </div>
            
            <div style="font-size: 1.1rem; font-weight: 900; color: #fbbf24; margin-bottom: 10px;">TỔNG TIỀN: {display_price}đ</div>
            <div style="font-size: 0.7rem; opacity: 0.6; line-height: 1.4;">Vui lòng quét mã để đặt cọc. Chuyến đi của bạn sẽ được kích hoạt ngay khi Admin xác nhận!</div>
        </div>
        """
    except Exception as e:
        safe_print(f"Error in get_payment_qr_card: {e}")
        return "⚠️ Có lỗi khi hiển thị thẻ hóa đơn."

def fetch_booking_by_id(token, booking_id):
    headers = {"Authorization": f"Bearer {token}"}
    for path in ["user/bookings/data", "admin/bookings/data"]:
        try:
            res = requests.get(f"{BE_URL}/{path}", headers=headers, timeout=5)
            if res.status_code == 200:
                bookings = res.json().get("data", [])
                target = next((b for b in bookings if str(b.get("id")) == str(booking_id)), None)
                if target:
                    if "car_name" not in target and "name" in target:
                        target["car_name"] = target["name"]
                    return target
        except: pass
    return None

def check_latest_confirmed_booking(token):
    try:
        headers = {"Authorization": f"Bearer {token}"}
        for path in ["user/bookings/data", "admin/bookings/data"]:
            response = requests.get(f"{BE_URL}/{path}", headers=headers, timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get("status") == 1 and data.get("data"):
                    confirmed = [b for b in data["data"] if b.get("tinh_trang") == 1]
                    if confirmed:
                        latest = confirmed[0]
                        return f"""
                        <div style="background: #f0fdf4; color: #166534; padding: 12px; border-radius: 10px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                            <i class="fa-solid fa-circle-check" style="font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-weight: bold;">Xác nhận thành công!</div>
                                <div style="font-size: 0.8rem;">Đơn hàng <b>{latest.get('ma_dat_xe')}</b> đã được Admin phê duyệt.</div>
                            </div>
                        </div>
                        """
    except: pass
    return None

# ======================
# API ENDPOINT
# ======================

@app.route("/chat", methods=["POST"])
def chat():
    try:
        data = request.get_json(silent=True) or {}
        sentence = data.get("message", "")
        token = data.get("token")
        booking_id = data.get("booking_id")

        safe_print(f"DEBUG: Received message='{sentence}', booking_id={booking_id}")

        # 1. Handle explicit booking invoice request
        if booking_id and token:
            target = fetch_booking_by_id(token, booking_id)
            if target:
                return jsonify({"status": 1, "response": get_payment_qr_card(target)})

        # 2. Check for updates notification
        if token and "thông báo" in sentence.lower():
            notification = check_latest_confirmed_booking(token)
            if notification:
                return jsonify({"status": 1, "response": notification})

        # 3. AI Inference
        tokenized_sentence = tokenize(sentence)
        X = bag_of_words(tokenized_sentence, all_words)
        X = X.reshape(1, X.shape[0])
        X = torch.from_numpy(X).to(device)

        output = model(X)
        _, predicted = torch.max(output, dim=1)
        tag = tags[predicted.item()]

        probs = torch.softmax(output, dim=1)
        prob = probs[0][predicted.item()]

        if prob.item() > 0.75:
            for intent in intents['intents']:
                if tag == intent["tag"]:
                    # Special check for payment request
                    if tag == "thanh_toan" and token:
                        headers = {"Authorization": f"Bearer {token}"}
                        for path in ["user/bookings/data", "admin/bookings/data"]:
                            try:
                                res = requests.get(f"{BE_URL}/{path}", headers=headers, timeout=5)
                                if res.status_code == 200:
                                    bookings = res.json().get("data", [])
                                    unpaid = [b for b in bookings if b.get("is_thanh_toan") == 0]
                                    if unpaid:
                                        return jsonify({"status": 1, "response": get_payment_qr_card(unpaid[0])})
                            except: pass
                    
                    return jsonify({"status": 1, "response": random.choice(intent['responses'])})
        
        return jsonify({
            "status": 1, 
            "response": "Tôi chưa hiểu ý bạn lắm. Bạn có thể hỏi về <b>Cách thuê xe</b>, <b>Giá xe VF8/VF9</b> hoặc yêu cầu <b>Mã thanh toán QR</b> nhé! 😊"
        })

    except Exception as e:
        safe_print(f"FATAL ERROR in /chat: {e}")
        # Return exact error for debugging
        return jsonify({"status": 0, "response": f"Lỗi hệ thống: {str(e)}"}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5005, debug=True)
