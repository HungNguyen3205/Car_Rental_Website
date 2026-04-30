<template>
  <div class="ai-chat-widget" :class="{ 'is-open': isOpen }">
    <!-- Chat Button -->
    <button class="chat-toggle-btn" @click="toggleChat">
      <div class="icon-container">
        <i v-if="!isOpen" class="fa-solid fa-robot pulse-icon"></i>
        <i v-else class="fa-solid fa-chevron-down"></i>
      </div>
      <span class="btn-label" v-if="!isOpen">Trợ lý AI</span>
    </button>

    <!-- Chat Window -->
    <div class="chat-window" v-if="isOpen">
      <div class="chat-header">
        <div class="bot-info">
          <div class="avatar-glow">
            <i class="fa-solid fa-robot"></i>
          </div>
          <div class="status-info">
            <h3>DriveRent Assistant</h3>
            <span class="status"><span class="dot"></span> Đang trực tuyến</span>
          </div>
        </div>
        <button class="close-btn" @click="isOpen = false">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="chat-messages" ref="messageContainer">
        <div v-for="(msg, index) in messages" :key="index" :class="['message-box', msg.role]">
          <div class="msg-avatar" v-if="msg.role === 'bot'">
            <i class="fa-solid fa-robot"></i>
          </div>
          <div class="msg-content glass-msg" v-html="msg.text"></div>
        </div>
        <div v-if="isTyping" class="message-box bot">
          <div class="msg-avatar"><i class="fa-solid fa-robot"></i></div>
          <div class="typing-indicator">
            <span></span><span></span><span></span>
          </div>
        </div>
      </div>

      <div class="chat-footer">
        <div class="input-area">
          <input 
            type="file" 
            ref="fileInput" 
            style="display: none" 
            accept="image/*"
            @change="handleFileUpload"
          />
          <button class="upload-btn" @click="$refs.fileInput.click()" title="Gửi ảnh hóa đơn">
            <i class="fa-solid fa-image"></i>
          </button>
          <input 
            type="text" 
            v-model="userInput" 
            @keydown.enter="sendMessage" 
            placeholder="Bạn muốn thuê xe gì?..."
          />
          <button @click="sendMessage" :disabled="!userInput.trim() || isTyping">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';
import baseRequest from '../../core/baseRequest';

const isOpen = ref(false);
const isTyping = ref(false);
const userInput = ref('');
const messageContainer = ref(null);
const fileInput = ref(null);
const currentBookingId = ref(null);
const messages = ref([
  { role: 'bot', text: 'Chào bạn! 👋 Mình là trợ lý ảo DriveRent. Mình có thể giúp bạn tìm xe và đặt xe nhanh chóng. Hôm nay bạn muốn đi đâu?' }
]);

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    // Automatically check for updates when opening chat
    checkForUpdates();
  }
};

const checkForUpdates = async () => {
    const userLocal = JSON.parse(localStorage.getItem('user')) || {};
    const token = localStorage.getItem('token_client');
    if (!token) return;

    try {
        const res = await axios.post('http://localhost:5005/chat', {
            user_id: userLocal.email || 'anonymous',
            message: "kiểm tra thông báo", // Special keyword for the new logic
            token: token
        });
        if (res.data.status && res.data.response.includes('div')) {
            // Only add if it's a special notification card
            messages.value.push({ role: 'bot', text: res.data.response });
            scrollToBottom();
        }
    } catch (err) { console.error("Update check failed", err); }
};

const scrollToBottom = async () => {
  await nextTick();
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
};

// Handle Handoff from Home page "Rent Now"
const handleAIHandoff = async (event) => {
    const { car, startDate, endDate, address } = event.detail;
    isOpen.value = true;
    isTyping.value = true;
    await scrollToBottom();
    
    const prompt = `I want to book ${car.name} from ${startDate} to ${endDate} at ${address || 'my location'}. Please confirm this with me.`;
    
    // Simulate user sending the hidden context
    messages.value.push({ role: 'bot', text: `Đang kết nối chi tiết đặt xe cho **${car.name}**...` });
    
    try {
        const userLocal = JSON.parse(localStorage.getItem('user')) || {};
        const token = localStorage.getItem('token_client');
        
        const res = await axios.post('http://localhost:5005/chat', {
            user_id: userLocal.email || 'anonymous',
            message: prompt,
            token: token // Pass token for authorized booking
        });

        if (res.data.status) {
            messages.value.push({ role: 'bot', text: formatBotResponse(res.data.response) });
        } else {
            messages.value.push({ role: 'bot', text: res.data.response || 'Hệ thống AI đang gặp lỗi.' });
        }
    } catch (error) {
        console.error("AI Error:", error);
        const errorMsg = error.response?.data?.response || 'Không thể kết nối với máy chủ AI. Vui lòng thử lại sau!';
        messages.value.push({ role: 'bot', text: errorMsg });
    } finally {
        isTyping.value = false;
        await scrollToBottom();
    }
};

const sendMessage = async () => {
  if (!userInput.value.trim() || isTyping.value) return;

  const userMsg = userInput.value;
  messages.value.push({ role: 'user', text: userMsg });
  userInput.value = '';
  isTyping.value = true;
  await scrollToBottom();

  try {
    const userLocal = JSON.parse(localStorage.getItem('user')) || {};
    const token = localStorage.getItem('token_client');

    const res = await axios.post('http://localhost:5005/chat', {
      user_id: userLocal.email || 'anonymous',
      message: userMsg,
      token: token
    });

    if (res.data.status) {
      messages.value.push({ role: 'bot', text: formatBotResponse(res.data.response) });
    } else {
      messages.value.push({ role: 'bot', text: res.data.response || 'Xin lỗi, mình đang gặp chút trục trặc.' });
    }
  } catch (error) {
    console.error("AI Error:", error);
    const errorMsg = error.response?.data?.response || 'Không thể kết nối với máy chủ AI. Bạn thử lại sau nhé!';
    messages.value.push({ role: 'bot', text: errorMsg });
  } finally {
    isTyping.value = false;
    await scrollToBottom();
  }
};

onMounted(() => {
    window.addEventListener('trigger-ai-booking', handleAIHandoff);
    window.addEventListener('show-booking-invoice', handleShowInvoice);
});

const handleShowInvoice = async (event) => {
    console.log("Received booking invoice event:", event.detail);
    const { booking } = event.detail;
    currentBookingId.value = booking.id; // Store for upload
    
    // Open chat immediately!
    isOpen.value = true;
    isTyping.value = true;
    
    // Small delay to ensure DB is updated and scrollToBottom works
    await new Promise(resolve => setTimeout(resolve, 500));
    await scrollToBottom();
    
    try {
        const token = localStorage.getItem('token_client') || localStorage.getItem('token_admin');
        const res = await axios.post('http://localhost:5005/chat', {
            message: "lấy hóa đơn",
            token: token,
            booking_id: booking.id
        });

        if (res.data.status) {
            messages.value.push({ role: 'bot', text: res.data.response });
            
            // Proactive follow-up message
            setTimeout(() => {
                messages.value.push({ 
                    role: 'bot', 
                    text: "✨ <b>Bước tiếp theo:</b> Bạn vui lòng thực hiện chuyển khoản theo mã QR trên. Sau khi hoàn tất, bạn có thể nhấn vào <b>biểu tượng Hình ảnh</b> bên dưới để gửi ảnh hóa đơn nhé! Admin sẽ duyệt xe ngay sau khi thấy ảnh. 🚗💨" 
                });
                scrollToBottom();
            }, 1000);
        } else {
            messages.value.push({ role: 'bot', text: "⚠️ Không thể tải hóa đơn ngay bây giờ. Bạn vui lòng thử nhắn 'lấy hóa đơn' để tôi kiểm tra lại nhé!" });
        }
    } catch (error) {
        console.error("Chatbot Invoice Error:", error);
        alert("Lỗi kết nối với Chatbot: " + (error.message || "Không rõ nguyên nhân"));
    } finally {
        isTyping.value = false;
        await scrollToBottom();
    }
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    if (!currentBookingId.value) {
        alert("Vui lòng thực hiện đặt xe hoặc xem hóa đơn trước khi gửi ảnh!");
        return;
    }

    isTyping.value = true;
    messages.value.push({ role: 'bot', text: " đang tải ảnh hóa đơn của bạn lên hệ thống..." });
    await scrollToBottom();

    const formData = new FormData();
    formData.append('bill_image', file);
    formData.append('booking_id', currentBookingId.value);

    try {
        const res = await baseRequest.post('user/bookings/upload-bill', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (res.data.status) {
            messages.value.push({ 
                role: 'bot', 
                text: `✅ <b>Tải lên thành công!</b><br>Tôi đã gửi ảnh hóa đơn này tới Admin. Bạn vui lòng đợi ít phút để hệ thống xác nhận nhé. <br><img src="${res.data.bill_url}" style="width: 100%; max-width: 200px; border-radius: 10px; margin-top: 10px;">` 
            });
        } else {
            messages.value.push({ role: 'bot', text: "❌ Lỗi: " + res.data.message });
        }
    } catch (error) {
        console.error("Upload Error", error);
        messages.value.push({ role: 'bot', text: "❌ Có lỗi khi tải ảnh lên. Vui lòng thử lại!" });
    } finally {
        isTyping.value = false;
        await scrollToBottom();
        // Reset input
        event.target.value = '';
    }
};

const formatBotResponse = (text) => {
  // Convert newlines to breaks or handle basic markdown-like syntax if needed
  return text.replace(/\n/g, '<br>');
};

watch(isOpen, (newVal) => {
  if (newVal) scrollToBottom();
});

</script>

<style scoped>
.ai-chat-widget {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 9999;
  font-family: var(--font-body);
}

.chat-toggle-btn {
  background: var(--grad-active);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.chat-toggle-btn:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(59, 130, 246, 0.5);
}

.pulse-icon {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.chat-window {
  position: absolute;
  bottom: 80px;
  right: 0;
  width: 380px;
  height: 550px;
  background: white;
  border-radius: 24px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.3);
  animation: slideUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.chat-header {
  padding: 20px;
  background: var(--color-primary);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bot-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-glow {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
}

.status-info h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
}

.status {
  font-size: 0.75rem;
  opacity: 0.8;
  display: flex;
  align-items: center;
  gap: 4px;
}

.dot {
  width: 6px;
  height: 6px;
  background: #10b981;
  border-radius: 50%;
}

.close-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
}

.chat-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #f8fafc;
}

.message-box {
  display: flex;
  gap: 10px;
  max-width: 85%;
}

.message-box.user {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.msg-avatar {
  width: 32px;
  height: 32px;
  background: white;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  color: var(--color-primary);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  flex-shrink: 0;
}

.msg-content {
  padding: 12px 16px;
  border-radius: 18px;
  font-size: 0.9rem;
  line-height: 1.5;
}

.message-box.bot .msg-content {
  background: white;
  color: var(--color-text-main);
  border-bottom-left-radius: 4px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.message-box.user .msg-content {
  background: var(--color-primary);
  color: white;
  border-bottom-right-radius: 4px;
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
  background: white;
  border-radius: 18px;
  border-bottom-left-radius: 4px;
}

.typing-indicator span {
  width: 6px;
  height: 6px;
  background: #cbd5e1;
  border-radius: 50%;
  animation: typing 1s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.chat-footer {
  padding: 20px;
  background: white;
  border-top: 1px solid #f1f5f9;
}

.input-area {
  display: flex;
  gap: 12px;
  background: #f1f5f9;
  padding: 8px 12px;
  border-radius: 16px;
}

.input-area input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.9rem;
  padding: 8px;
}

.input-area button {
  background: var(--color-primary);
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-btn {
  background: white !important;
  color: #64748b !important;
  border: 1px solid #e2e8f0 !important;
}

.upload-btn:hover {
  background: #f8fafc !important;
  color: var(--color-primary) !important;
}

.input-area button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.input-area button:hover:not(:disabled) {
  transform: scale(1.05);
}

/* Scrollbar Style */
.chat-messages::-webkit-scrollbar {
  width: 5px;
}
.chat-messages::-webkit-scrollbar-track {
  background: transparent;
}
.chat-messages::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
</style>
