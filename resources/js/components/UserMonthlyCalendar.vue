<template>
    <Dialog
        :visible="visible"
        :header="dialogTitle"
        :modal="true"
        :style="{ width: '95%', maxWidth: '900px' }"
        @update:visible="$emit('update:visible', $event)"
    >
        <div class="calendar-container">
            <div class="calendar-header">
                <Button 
                    icon="pi pi-chevron-left" 
                    @click="previousMonth"
                    text
                    rounded
                />
                <div class="month-selector">
                    <Dropdown 
                        v-model="selectedMonth" 
                        :options="months" 
                        optionLabel="label" 
                        optionValue="value"
                        @change="fetchMonthlyData"
                    />
                    <Dropdown 
                        v-model="selectedYear" 
                        :options="years" 
                        @change="fetchMonthlyData"
                    />
                </div>
                <Button 
                    icon="pi pi-chevron-right" 
                    @click="nextMonth"
                    text
                    rounded
                />
            </div>

            <div v-if="loading" class="loading-container">
                <ProgressSpinner />
            </div>

            <div v-else class="calendar-wrapper">
                <div class="calendar-grid">
                    <div v-for="day in calendarDays" :key="day.date" class="day-cell">
                        <div class="day-number">{{ day.dayOfMonth }}</div>
                        <div class="prayer-boxes">
                            <div
                                class="prayer-box dhuha"
                                :class="getPrayerClass(day, 'dhuha')"
                                :title="getPrayerTooltip(day, 'dhuha')"
                            >
                                <i class="pi pi-check" v-if="getPrayerClass(day, 'dhuha') === 'sholat'"></i>
                                <i class="pi pi-times-circle" v-else-if="getPrayerClass(day, 'dhuha') === 'halangan'"></i>
                                <i class="pi pi-minus-circle" v-else></i>
                            </div>
                            <div
                                class="prayer-box dhuhur"
                                :class="getPrayerClass(day, 'dhuhur')"
                                :title="getPrayerTooltip(day, 'dhuhur')"
                            >
                                <i class="pi pi-check" v-if="getPrayerClass(day, 'dhuhur') === 'sholat'"></i>
                                <i class="pi pi-times-circle" v-else-if="getPrayerClass(day, 'dhuhur') === 'halangan'"></i>
                                <i class="pi pi-minus-circle" v-else></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="legend">
                <div class="legend-title">Status Sholat:</div>
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-indicator sholat">
                            <i class="pi pi-check"></i>
                        </div>
                        <span>Sholat</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-indicator halangan">
                            <i class="pi pi-exclamation-triangle"></i>
                        </div>
                        <span>Halangan</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-indicator tidak-hadir">
                            <i class="pi pi-minus"></i>
                        </div>
                        <span>Tidak Hadir</span>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '../api/axios';

const props = defineProps({
    visible: Boolean,
    userId: Number,
    userName: String,
});

const emit = defineEmits(['update:visible']);

const toast = useToast();
const loading = ref(false);
const records = ref([]);

const currentDate = new Date();
const selectedMonth = ref(currentDate.getMonth());
const selectedYear = ref(currentDate.getFullYear());

const months = [
    { label: 'Januari', value: 0 },
    { label: 'Februari', value: 1 },
    { label: 'Maret', value: 2 },
    { label: 'April', value: 3 },
    { label: 'Mei', value: 4 },
    { label: 'Juni', value: 5 },
    { label: 'Juli', value: 6 },
    { label: 'Agustus', value: 7 },
    { label: 'September', value: 8 },
    { label: 'Oktober', value: 9 },
    { label: 'November', value: 10 },
    { label: 'Desember', value: 11 },
];

const years = computed(() => {
    const currentYear = new Date().getFullYear();
    const yearList = [];
    for (let i = currentYear - 2; i <= currentYear + 1; i++) {
        yearList.push(i);
    }
    return yearList;
});

const dialogTitle = computed(() => {
    return `${props.userName} - ${months[selectedMonth.value].label} ${selectedYear.value}`;
});

const calendarDays = computed(() => {
    const year = selectedYear.value;
    const month = selectedMonth.value;
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const days = [];

    for (let day = 1; day <= daysInMonth; day++) {
        const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayRecords = records.value.filter(r => {
            // Normalize date comparison - remove time part if exists
            const recordDate = r.date.split('T')[0]; // Handle datetime format
            return recordDate === date;
        });
        
        days.push({
            date,
            dayOfMonth: day,
            records: dayRecords,
        });
    }

    return days;
});

const fetchMonthlyData = async () => {
    if (!props.userId) return;

    loading.value = true;
    try {
        const year = selectedYear.value;
        const month = selectedMonth.value;
        const startDate = `${year}-${String(month + 1).padStart(2, '0')}-01`;
        const endDate = `${year}-${String(month + 1).padStart(2, '0')}-${new Date(year, month + 1, 0).getDate()}`;

        const response = await api.get('/prayer-records', {
            params: {
                user_id: props.userId,
                start_date: startDate,
                end_date: endDate,
                per_page: 1000, // Increase to get all records for the month
            },
        });

        // Handle both paginated and non-paginated responses
        records.value = response.data.data || response.data || [];
        
        console.log('Fetched records:', records.value.length);
        console.log('Sample record:', records.value[0]);
    } catch (error) {
        console.error('Fetch error:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch monthly data',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const getPrayerClass = (day, prayerType) => {
    const record = day.records.find(r => r.prayer_type === prayerType);
    
    console.log(`Day: ${day.date}, Type: ${prayerType}, Record:`, record);
    
    if (!record) return 'tidak-hadir';
    
    if (record.status === 'sholat') return 'sholat';
    if (record.status === 'halangan') return 'halangan';
    if (record.status === 'tidak_sholat') return 'tidak-hadir';
    
    return 'tidak-hadir';
};

const getPrayerTooltip = (day, prayerType) => {
    const record = day.records.find(r => r.prayer_type === prayerType);
    const prayerName = prayerType === 'dhuha' ? 'Dhuha' : 'Dhuhur';
    
    if (!record) return `${prayerName}: Tidak ada data`;
    
    if (record.status === 'sholat') return `${prayerName}: Sholat`;
    if (record.status === 'halangan') return `${prayerName}: Halangan${record.notes ? ' - ' + record.notes : ''}`;
    return `${prayerName}: Tidak Sholat`;
};

const previousMonth = () => {
    if (selectedMonth.value === 0) {
        selectedMonth.value = 11;
        selectedYear.value--;
    } else {
        selectedMonth.value--;
    }
    fetchMonthlyData();
};

const nextMonth = () => {
    if (selectedMonth.value === 11) {
        selectedMonth.value = 0;
        selectedYear.value++;
    } else {
        selectedMonth.value++;
    }
    fetchMonthlyData();
};

watch(() => props.visible, (newVal) => {
    if (newVal && props.userId) {
        fetchMonthlyData();
    }
});
</script>

<style scoped>
/* Calendar container with liquid glass theme */
.calendar-container {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.month-selector {
    display: flex;
    gap: 0.75rem;
    flex: 1;
    justify-content: center;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}

.calendar-wrapper {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 1rem;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    overflow-x: auto;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 3px;
    min-width: 600px;
}

.day-cell {
    aspect-ratio: 1;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 3px 2px;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: all 0.2s ease;
    min-width: 0;
}

.day-cell:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.day-number {
    font-size: 0.7rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.95);
    text-align: center;
    margin-bottom: 2px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.prayer-boxes {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.prayer-box {
    flex: 1;
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: help;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    min-height: 18px;
    position: relative;
    overflow: hidden;
}

.prayer-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
    pointer-events: none;
}

.prayer-box:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Prayer status colors with liquid glass effect */
.prayer-box.sholat {
    background: rgba(16, 185, 129, 0.3) !important;
    border: 1px solid rgba(16, 185, 129, 0.5) !important;
    color: #86efac;
}

.prayer-box.halangan {
    background: rgba(239, 68, 68, 0.3) !important;
    border: 1px solid rgba(239, 68, 68, 0.5) !important;
    color: #fca5a5;
}

.prayer-box.tidak-hadir {
    background: rgba(107, 114, 128, 0.2) !important;
    border: 1px solid rgba(107, 114, 128, 0.3) !important;
    color: rgba(255, 255, 255, 0.6);
}

/* Icon styling */
.prayer-box i {
    font-size: 0.6rem;
}

/* Enhanced legend with liquid glass */
.legend {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1rem;
}

.legend-title {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.9rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.legend-indicator {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.legend-indicator.sholat {
    background: rgba(16, 185, 129, 0.3) !important;
    border-color: rgba(16, 185, 129, 0.5) !important;
    color: #86efac;
}

.legend-indicator.halangan {
    background: rgba(239, 68, 68, 0.3) !important;
    border-color: rgba(239, 68, 68, 0.5) !important;
    color: #fca5a5;
}

.legend-indicator.tidak-hadir {
    background: rgba(107, 114, 128, 0.2) !important;
    border-color: rgba(107, 114, 128, 0.3) !important;
    color: rgba(255, 255, 255, 0.6);
}

.legend-item span {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Responsive styles */
@media (min-width: 480px) {
    .calendar-grid {
        gap: 4px;
    }

    .day-cell {
        padding: 4px 3px;
    }

    .day-number {
        font-size: 0.75rem;
    }

    .prayer-box {
        min-height: 20px;
        font-size: 0.7rem;
    }

    .prayer-box i {
        font-size: 0.65rem;
    }
}

@media (min-width: 768px) {
    .calendar-container {
        gap: 1.5rem;
    }

    .calendar-header {
        padding: 1rem;
    }

    .month-selector {
        gap: 1rem;
    }

    .loading-container {
        min-height: 250px;
    }

    .calendar-wrapper {
        padding: 1.25rem;
    }

    .calendar-grid {
        gap: 5px;
    }

    .day-cell {
        border-radius: 10px;
        padding: 5px 4px;
    }

    .day-number {
        font-size: 0.875rem;
        margin-bottom: 3px;
    }

    .prayer-box {
        border-radius: 8px;
        min-height: 24px;
        font-size: 0.75rem;
    }

    .prayer-box i {
        font-size: 0.7rem;
    }

    .legend {
        padding: 1.25rem;
    }

    .legend-title {
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    .legend-items {
        gap: 1.5rem;
    }

    .legend-item {
        padding: 0.75rem 1rem;
        gap: 0.75rem;
    }

    .legend-indicator {
        width: 28px;
        height: 28px;
        font-size: 0.875rem;
    }

    .legend-item span {
        font-size: 0.9rem;
    }
}

@media (min-width: 1024px) {
    .calendar-grid {
        gap: 6px;
    }

    .calendar-wrapper {
        padding: 1.5rem;
    }
}

/* Dialog responsive overrides */
:deep(.p-dialog) {
    width: 98vw !important;
    max-width: 900px !important;
}

:deep(.p-dialog .p-dialog-header) {
    padding: 1rem 1.25rem;
    font-size: 1rem;
    font-weight: 600;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1rem 1.25rem;
}

@media (min-width: 768px) {
    :deep(.p-dialog .p-dialog-header) {
        padding: 1.25rem 1.5rem;
        font-size: 1.125rem;
    }

    :deep(.p-dialog .p-dialog-content) {
        padding: 1.25rem 1.5rem;
    }
}

/* Weekend highlighting */
.day-cell:nth-child(7n),
.day-cell:nth-child(7n-1) {
    background: rgba(255, 255, 255, 0.15);
}

/* Animation for status changes */
.prayer-box {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>