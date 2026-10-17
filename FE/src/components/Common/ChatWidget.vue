<template>
  <div class="ai-chat-widget" :class="{ 'is-open': isOpen }">
    <!-- Chat Toggle Button -->
    <button 
      class="chat-toggle-btn" 
      @click="toggleChat"
      :aria-expanded="isOpen"
      aria-label="Trợ lý ảo DriveRent"
      type="button"
    >
      <div class="icon-container">
        <i v-if="!isOpen" class="fa-solid fa-robot pulse-icon"></i>
        <i v-else class="fa-solid fa-chevron-down"></i>
      </div>
      <span class="btn-label" v-if="!isOpen">Hỗ trợ 24/7</span>
    </button>

    <!-- Chat Window -->
    <Transition name="chat-pop">
      <div class="chat-window card" v-if="isOpen" role="dialog" aria-modal="true" aria-label="Khung trò chuyện với trợ lý">
        <!-- Header -->
        <div class="chat-header">
          <div class="bot-info">
            <div class="avatar-glow">
              <i class="fa-solid fa-robot"></i>
            </div>
            <div class="status-info">
              <h3>Trợ lý ảo DriveRent</h3>
              <span class="status"><span class="dot"></span> Trực tuyến 24/7</span>
            </div>
          </div>
          <button class="close-btn" @click="isOpen = false" aria-label="Đóng cửa sổ chat" type="button">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Messages Area -->
        <div class="chat-messages" ref="messageContainer" @scroll="handleUserScroll">
          <div v-for="(msg, index) in messages" :key="index" :class="['message-box', msg.role]">
            <div class="msg-avatar" v-if="msg.role === 'bot'">
              <i class="fa-solid fa-robot"></i>
            </div>
            <div class="msg-content" v-html="sanitizeHtml(msg.text)"></div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="isTyping" class="message-box bot">
            <div class="msg-avatar"><i class="fa-solid fa-robot"></i></div>
            <div class="typing-indicator">
              <span></span><span></span><span></span>
            </div>
          </div>
        </div>

        <!-- Footer Input -->
        <div class="chat-footer">
          <div class="input-area">
            <input 
              type="file" 
              ref="fileInput" 
              style="display: none" 
              accept="image/jpeg,image/png,image/webp,image/jpg"
              @change="handleFileUpload"
            />
            <button 
              type="button" 
              class="upload-btn" 
              @click="$refs.fileInput.click()" 
              title="Gửi ảnh biên lai chuyển khoản"
              :disabled="isTyping"
            >
              <i class="fa-solid fa-image"></i>
            </button>
            <input 
              type="text" 
              v-model="userInput" 
              @keydown.enter.prevent="sendMessage" 
              placeholder="Hỏi về thuê xe, giá cả, hợp đồng..."
              :disabled="isTyping"
            />
            <button 
              type="button" 
              class="send-btn" 
              @click="sendMessage" 
              :disabled="!userInput.trim() || isTyping"
              aria-label="Gửi tin nhắn"
            >
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import axios from 'axios';
import baseRequest from '../../core/baseRequest';
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({ position: "top-right" });

const isOpen = ref(false);
const isTyping = ref(false);
const userInput = ref('');
const messageContainer = ref(null);
const fileInput = ref(null);
const currentBookingId = ref(null);
const userIsScrolling = ref(false);

const messages = ref([
  { role: 'bot', text: 'Xin chào! 👋 Tôi là trợ lý ảo DriveRent. Tôi có thể hỗ trợ bạn tìm kiếm xe điện, tư vấn lịch trình hoặc hướng dẫn thanh toán. Bạn đang cần hỗ trợ gì hôm nay?' }
]);

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    nextTick(() => scrollToBottom(true));
  }
};

const handleUserScroll = () => {
  if (!messageContainer.value) return;
  const { scrollTop, scrollHeight, clientHeight } = messageContainer.value;
  // If user scrolled up by more than 100px from bottom, mark as manual scroll
  userIsScrolling.value = (scrollHeight - scrollTop - clientHeight) > 100;
};

const scrollToBottom = async (force = false) => {
  await nextTick();
  if (messageContainer.value && (force || !userIsScrolling.value)) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
};

// Basic HTML sanitization for safe bot display
const sanitizeHtml = (html) => {
  if (!html) return '';
  // Allow safe basic formatting tags while neutralizing script tags
  return html
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/on\w+="[^"]*"/gi, '');
};

const formatBotResponse = (text) => {
  if (!text) return '';
  return text.replace(/\n/g, '<br>');
};

// Handle Handoff from Home page or Cars
const handleAIHandoff = async (event) => {
  const { car, startDate, endDate, address } = event.detail;
  isOpen.value = true;
  isTyping.value = true;
  await scrollToBottom(true);
  
  const prompt = `Tôi muốn thuê xe ${car.name} từ ${startDate} đến ${endDate} tại ${address || 'showroom'}. Hãy hỗ trợ xác nhận đơn cho tôi.`;
  
  messages.value.push({ role: 'bot', text: `Đang kết nối chi tiết đặt xe cho dòng **${car.name}**...` });
  
  try {
    const userLocal = JSON.parse(localStorage.getItem('user')) || {};
    const token = localStorage.getItem('token_client');
    
    const res = await axios.post('http://localhost:5005/chat', {
      user_id: userLocal.email || 'anonymous',
      message: prompt,
      token: token
    }, { timeout: 8000 });

    if (res.data && res.data.status) {
      messages.value.push({ role: 'bot', text: formatBotResponse(res.data.response) });
    } else {
      messages.value.push({ role: 'bot', text: res.data?.response || 'Hệ thống AI tạm thời bận. Bạn vui lòng thử lại sau.' });
    }
  } catch (error) {
    messages.value.push({ role: 'bot', text: 'Không thể kết nối với máy chủ AI. Quý khách vui lòng thử lại sau hoặc liên hệ Hotline 1900 1234.' });
  } finally {
    isTyping.value = false;
    await scrollToBottom(true);
  }
};

const handleShowInvoice = async (event) => {
  const { booking } = event.detail;
  if (!booking) return;
  
  currentBookingId.value = booking.id;
  isOpen.value = true;
  isTyping.value = true;
  await scrollToBottom(true);
  
  try {
    const token = localStorage.getItem('token_client') || localStorage.getItem('token_admin');
    const res = await axios.post('http://localhost:5005/chat', {
      message: "lấy hóa đơn",
      token: token,
      booking_id: booking.id
    }, { timeout: 8000 });

    if (res.data && res.data.status) {
      messages.value.push({ role: 'bot', text: res.data.response });
      setTimeout(() => {
        messages.value.push({ 
          role: 'bot', 
          text: "✨ <b>Bước tiếp theo:</b> Quý khách vui lòng chuyển khoản theo thông tin trên và nhấn vào <b>biểu tượng Hình ảnh</b> bên dưới để gửi biên lai. Đơn sẽ được duyệt ngay sau khi nhận được biên lai! 🚗💨" 
        });
        scrollToBottom(true);
      }, 600);
    } else {
      messages.value.push({ role: 'bot', text: `Đơn hàng #${booking.ma_dat_xe} đã được ghi nhận. Bạn có thể xem chi tiết trong mục Chuyến đi của tôi!` });
    }
  } catch (error) {
    messages.value.push({ role: 'bot', text: `Đơn hàng #${booking.ma_dat_xe} đã được tạo thành công! Bạn có thể xem chi tiết và tải biên lai tại mục **Chuyến đi của tôi**.` });
  } finally {
    isTyping.value = false;
    await scrollToBottom(true);
  }
};

const sendMessage = async () => {
  if (!userInput.value.trim() || isTyping.value) return;

  const userMsg = userInput.value.trim();
  messages.value.push({ role: 'user', text: userMsg });
  userInput.value = '';
  isTyping.value = true;
  await scrollToBottom(true);

  try {
    const userLocal = JSON.parse(localStorage.getItem('user')) || {};
    const token = localStorage.getItem('token_client');

    const res = await axios.post('http://localhost:5005/chat', {
      user_id: userLocal.email || 'anonymous',
      message: userMsg,
      token: token
    }, { timeout: 8000 });

    if (res.data && res.data.status) {
      messages.value.push({ role: 'bot', text: formatBotResponse(res.data.response) });
    } else {
      messages.value.push({ role: 'bot', text: res.data?.response || 'Xin lỗi, tôi đang gặp trục trặc kỹ thuật. Vui lòng thử lại sau.' });
    }
  } catch (error) {
    messages.value.push({ role: 'bot', text: 'Hiện không thể kết nối tới máy chủ AI Assistant. Quý khách vui lòng gọi Hotline 1900 1234 để được hỗ trợ tức thì.' });
  } finally {
    isTyping.value = false;
    await scrollToBottom();
  }
};

const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    toaster.warning("Kích thước ảnh biên lai tối đa là 5MB!");
    return;
  }

  if (!currentBookingId.value) {
    toaster.info("Bạn có thể tải ảnh biên lai trực tiếp từ trang 'Chuyến đi của tôi'!");
    messages.value.push({ role: 'bot', text: 'Vui lòng truy cập **Chuyến đi của tôi** để tải biên lai cho đơn đặt xe cụ thể của bạn nhé!' });
    return;
  }

  isTyping.value = true;
  messages.value.push({ role: 'bot', text: "Đang tải ảnh biên lai chuyển khoản lên hệ thống..." });
  await scrollToBottom(true);

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
        text: `✅ <b>Tải lên thành công!</b><br>Hệ thống đã nhận được ảnh biên lai. Admin sẽ đối soát và duyệt đơn sớm nhất cho bạn nhé.<br><img src="${res.data.bill_url}" style="width: 100%; max-width: 200px; border-radius: 8px; margin-top: 8px;">` 
      });
      toaster.success("Tải biên lai thành công!");
    } else {
      messages.value.push({ role: 'bot', text: "❌ Không thể tải biên lai: " + (res.data.message || 'Lỗi không xác định') });
    }
  } catch (error) {
    messages.value.push({ role: 'bot', text: "❌ Có lỗi khi tải ảnh lên. Vui lòng thử lại từ trang Chuyến đi của tôi!" });
  } finally {
    isTyping.value = false;
    await scrollToBottom(true);
    event.target.value = '';
  }
};

const handleOpenAIChat = () => {
  isOpen.value = true;
  nextTick(() => scrollToBottom(true));
};

onMounted(() => {
  window.addEventListener('trigger-ai-booking', handleAIHandoff);
  window.addEventListener('show-booking-invoice', handleShowInvoice);
  window.addEventListener('open-ai-chat', handleOpenAIChat);
});

onUnmounted(() => {
  window.removeEventListener('trigger-ai-booking', handleAIHandoff);
  window.removeEventListener('show-booking-invoice', handleShowInvoice);
  window.removeEventListener('open-ai-chat', handleOpenAIChat);
});

watch(isOpen, (newVal) => {
  if (newVal) scrollToBottom(true);
});
</script>

<style scoped>
.ai-chat-widget {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 999;
  font-family: var(--font-body);
}

/* Chat Toggle Button */
.chat-toggle-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 18px 8px 8px;
  background: var(--bg-dark);
  color: #ffffff;
  border-radius: var(--radius-full);
  box-shadow: 0 10px 30px rgba(11, 18, 32, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.12);
  cursor: pointer;
  transition: transform var(--duration-fast) var(--ease-spring),
              box-shadow var(--duration-fast) var(--ease-spring);
}

.chat-toggle-btn:hover {
  transform: scale(1.04);
  box-shadow: var(--shadow-glow);
}

.icon-container {
  width: 42px;
  height: 42px;
  background: var(--grad-active);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
}

.pulse-icon {
  animation: pulseGlow 2.5s infinite;
}

.btn-label {
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: -0.01em;
}

/* Chat Window */
.chat-window {
  position: absolute;
  bottom: calc(100% + 16px);
  right: 0;
  width: 380px;
  height: 560px;
  max-height: calc(100vh - 120px);
  background: #ffffff;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-premium);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transform-origin: bottom right;
}

/* Header */
.chat-header {
  padding: 16px 20px;
  background: var(--bg-dark);
  color: #ffffff;
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
  width: 38px;
  height: 38px;
  background: var(--grad-active);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}

.status-info h3 {
  font-size: 0.975rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 2px;
}

.status-info .status {
  font-size: 0.75rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 6px;
}

.status-info .dot {
  width: 7px;
  height: 7px;
  background: #10b981;
  border-radius: 50%;
  animation: pulseGlow 1.8s infinite;
}

.close-btn {
  color: #94a3b8;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-xs);
  transition: all var(--duration-fast);
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

/* Messages */
.chat-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
  background: var(--bg-surface-sub);
}

.message-box {
  display: flex;
  gap: 10px;
  max-width: 85%;
}

.message-box.bot {
  align-self: flex-start;
}

.message-box.user {
  align-self: flex-end;
}

.msg-avatar {
  width: 28px;
  height: 28px;
  background: var(--color-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  flex-shrink: 0;
  margin-top: 4px;
}

.msg-content {
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 0.885rem;
  line-height: 1.55;
  box-shadow: var(--shadow-xs);
  word-break: break-word;
}

.message-box.bot .msg-content {
  background: #ffffff;
  color: var(--color-text-main);
  border-top-left-radius: 4px;
}

.message-box.user .msg-content {
  background: var(--color-primary);
  color: #ffffff;
  border-top-right-radius: 4px;
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 12px 16px;
  background: #ffffff;
  border-radius: 16px;
  border-top-left-radius: 4px;
  box-shadow: var(--shadow-xs);
}

.typing-indicator span {
  width: 6px;
  height: 6px;
  background: var(--color-text-muted);
  border-radius: 50%;
  animation: typingBounce 1.2s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
  0%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-5px); }
}

/* Footer */
.chat-footer {
  padding: 14px 16px;
  background: #ffffff;
  border-top: 1px solid var(--color-border);
}

.input-area {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-surface-sub);
  padding: 6px 8px 6px 12px;
  border-radius: var(--radius-full);
  border: 1px solid transparent;
  transition: all var(--duration-fast);
}

.input-area:focus-within {
  background: #ffffff;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.15);
}

.upload-btn {
  color: var(--color-text-muted);
  padding: 6px;
  font-size: 1.05rem;
  transition: color var(--duration-fast);
}

.upload-btn:hover:not(:disabled) {
  color: var(--color-primary);
}

.input-area input[type="text"] {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: 0.885rem;
  color: var(--color-text-main);
}

.send-btn {
  width: 34px;
  height: 34px;
  background: var(--color-primary);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  transition: all var(--duration-fast);
}

.send-btn:hover:not(:disabled) {
  background: var(--color-primary-hover);
  transform: scale(1.05);
}

.send-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* Transitions */
.chat-pop-enter-active,
.chat-pop-leave-active {
  transition: transform var(--duration-normal) var(--ease-spring),
              opacity var(--duration-normal) var(--ease-smooth);
}

.chat-pop-enter-from,
.chat-pop-leave-to {
  opacity: 0;
  transform: scale(0.92) translateY(16px);
}

@media (max-width: 480px) {
  .ai-chat-widget {
    bottom: 20px;
    right: 20px;
  }
  .chat-window {
    width: calc(100vw - 40px);
    height: 75vh;
  }
}
</style>
