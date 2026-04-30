<template>
  <div class="bot-client">
    <!-- Chatbot Toggle Button -->
    <!-- <button class="chatbot-toggle" @click="isOpen = !isOpen" :class="{ 'is-open': isOpen }">
      <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button> -->

    <!-- Floating AI Chatbot Widget -->
    <!-- <div class="chatbot-widget card" :class="{ 'widget-open': isOpen }">
      <div class="chat-header">
        <div class="chat-avatar">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="10" x="3" y="11" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" x2="8" y1="16" y2="16"/><line x1="16" x2="16" y1="16" y2="16"/></svg>
        </div>
        <div class="chat-title">
          <h4>Trợ lý DriveRentals</h4>
          <span class="status">Sẵn sàng hỗ trợ</span>
        </div>
      </div>
      
      <div class="chat-body">
        <div class="msg ai">
          <div class="msg-content">Xin chào! 👋 Tôi là trợ lý AI ảo của hệ thống. Tôi có thể giúp gì cho quá trình thuê xe của bạn?</div>
          <span class="msg-time">Vừa xong</span>
        </div>
      </div>
      
      <div class="chat-input">
        <input type="text" placeholder="Nhập câu hỏi của bạn..." />
        <button class="send-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
    </div> -->
  </div>
</template>

<script setup>
import { ref } from 'vue';

const isOpen = ref(false);
</script>

<script>
export default {
    name: "BotClient"
}
</script>

<style scoped>
.bot-client {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 16px;
}

.chatbot-toggle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background-color: var(--color-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-lg);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chatbot-toggle:hover {
  transform: scale(1.05);
  background-color: var(--color-primary-hover);
}

.chatbot-toggle.is-open {
  background-color: #f1f5f9;
  color: #64748b;
  box-shadow: var(--shadow-sm);
}

.chatbot-widget {
  width: 360px;
  height: 500px;
  display: flex;
  flex-direction: column;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--shadow-float);
  transform-origin: bottom right;
  transform: scale(0.9);
  opacity: 0;
  pointer-events: none;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  position: absolute;
  bottom: 72px;
  right: 0;
}

.chatbot-widget.widget-open {
  transform: scale(1);
  opacity: 1;
  pointer-events: auto;
}

.chat-header {
  padding: 20px;
  background: var(--color-primary);
  color: white;
  display: flex;
  gap: 12px;
  align-items: center;
}

.chat-avatar {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-title h4 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 2px;
}

.chat-title .status {
  font-size: 0.75rem;
  opacity: 0.9;
  display: flex;
  align-items: center;
  gap: 4px;
}

.chat-title .status::before {
  content: "";
  display: block;
  width: 6px;
  height: 6px;
  background: #4ade80;
  border-radius: 50%;
}

.chat-body {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #f8fafc;
}

.msg {
  display: flex;
  flex-direction: column;
  max-width: 85%;
}

.msg.ai {
  align-self: flex-start;
}

.msg.user {
  align-self: flex-end;
}

.msg-content {
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 0.9375rem;
  line-height: 1.5;
  box-shadow: var(--shadow-sm);
}

.msg.ai .msg-content {
  background: white;
  color: var(--color-text-main);
  border-bottom-left-radius: 4px;
}

.msg.user .msg-content {
  background: var(--color-primary);
  color: white;
  border-bottom-right-radius: 4px;
}

.msg-time {
  font-size: 0.7rem;
  color: var(--color-text-muted);
  margin-top: 4px;
  padding: 0 4px;
}

.msg.user .msg-time {
  align-self: flex-end;
}

.chat-input {
  padding: 16px;
  background: white;
  border-top: 1px solid var(--color-border);
  display: flex;
  gap: 8px;
  align-items: center;
}

.chat-input input {
  flex: 1;
  background: #f1f5f9;
  border: 1px solid transparent;
  padding: 12px 16px;
  border-radius: var(--radius-full);
  font-size: 0.9375rem;
  transition: all 0.2s;
  outline: none;
}

.chat-input input:focus {
  background: white;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.send-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-primary);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.send-btn:hover {
  background: var(--color-primary-hover);
  transform: scale(1.05);
}

@media (max-width: 480px) {
  .chatbot-widget {
    width: calc(100vw - 48px);
    height: 60vh;
  }
}
</style>
