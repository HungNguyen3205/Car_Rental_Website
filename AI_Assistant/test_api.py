import requests
import json

url = "http://localhost:5005/chat"
data = {"message": "Xin chào, bạn là ai?", "user_id": "test_user"}
headers = {"Content-Type": "application/json"}

try:
    response = requests.post(url, json=data, headers=headers)
    print(f"Status Code: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2, ensure_ascii=False)}")
except Exception as e:
    print(f"Error: {e}")
