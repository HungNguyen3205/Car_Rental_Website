from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import google.generativeai as genai
import os
from dotenv import load_dotenv

load_dotenv()

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allows all origins
    allow_credentials=True,
    allow_methods=["*"],  # Allows all methods
    allow_headers=["*"],  # Allows all headers
)

# Configure Gemini AI
gemini_api_key = os.getenv("GEMINI_API_KEY")
if gemini_api_key:
    genai.configure(api_key=gemini_api_key)
else:
    print("WARNING: GEMINI_API_KEY not found in environment variables.")

class ChatRequest(BaseModel):
    message: str

@app.post("/api/chat")
async def chat_with_ai(request: ChatRequest):
    if not gemini_api_key:
        raise HTTPException(status_code=500, detail="Gemini API key not configured")
        
    try:
        model = genai.GenerativeModel('gemini-pro')
        prompt = f"""
        Bạn là nhân viên tư vấn dịch vụ thuê xe tự lái. Website của bạn có giao diện Vibe Code hiện đại.
        Nhiệm vụ của bạn là hỗ trợ khách hàng trả lời các câu hỏi về việc thuê xe, giá cả, thủ tục, v.v.
        Hãy trả lời ngắn gọn, thân thiện và chuyên nghiệp.

        Khách hàng hỏi: {request.message}
        """
        response = model.generate_content(prompt)
        return {"response": response.text}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/")
async def root():
    return {"message": "Chatbot AI API is running"}
