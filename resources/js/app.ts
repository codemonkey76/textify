import '../css/app.css';
import { createApp } from 'vue';
import NavBar from './components/NavBar.vue';
import AccountTable from './components/AccountTable.vue';

const app = createApp({});
app.component('nav-bar', NavBar);
app.component('account-table', AccountTable);
app.mount('#app');
