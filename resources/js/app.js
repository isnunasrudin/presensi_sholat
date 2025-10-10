import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import router from './router';
import App from './App.vue';

// PrimeVue components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Menu from 'primevue/menu';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Paginator from 'primevue/paginator';
import DataView from 'primevue/dataview';
import ProgressSpinner from 'primevue/progressspinner';
import Badge from 'primevue/badge';
import Timeline from 'primevue/timeline';
import Fieldset from 'primevue/fieldset';
import SelectButton from 'primevue/selectbutton';
import Knob from 'primevue/knob';
import Message from 'primevue/message';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.dark-mode',
        }
    }
});
app.use(ToastService);
app.use(ConfirmationService);

// Register PrimeVue components
app.component('Button', Button);
app.component('InputText', InputText);
app.component('Password', Password);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Dialog', Dialog);
app.component('Toast', Toast);
app.component('ConfirmDialog', ConfirmDialog);
app.component('Dropdown', Dropdown);
app.component('Calendar', Calendar);
app.component('Textarea', Textarea);
app.component('Menu', Menu);
app.component('Card', Card);
app.component('Chart', Chart);
app.component('Paginator', Paginator);
app.component('DataView', DataView);
app.component('ProgressSpinner', ProgressSpinner);
app.component('Badge', Badge);
app.component('Timeline', Timeline);
app.component('Fieldset', Fieldset);
app.component('SelectButton', SelectButton);
app.component('Knob', Knob);
app.component('Message', Message);

app.mount('#app');
