<template>
    <div class="wrapper">
        <TopClient></TopClient>
        <main class="main-content">
            <router-view></router-view>
        </main>
        <BotClient></BotClient>
    </div>
</template>

<script>
// Nhập các thành phần Layout Top và Bot tương tự FE_Hospital_Management
import TopClient from "../../components/Client/TopClient.vue";
import BotClient from "../../components/Client/BotClient.vue";

export default {
    name: "ClientWrapper",
    components: {
        BotClient, TopClient
    }
}
</script>

<style scoped>
/* Removed app-container to allow edge-to-edge layouts */
</style>
