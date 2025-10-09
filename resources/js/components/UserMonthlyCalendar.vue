<template>
    <Dialog
        :visible="visible"
        :header="dialogTitle"
        :modal="true"
        :style="{ width: '90%', maxWidth: '800px' }"
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

            <div v-else class="calendar-grid">
                <div v-for="day in calendarDays" :key="day.date" class="day-cell">
                    <div class="day-number">{{ day.dayOfMonth }}</div>
                    <div class="prayer-boxes">
                        <div 
                            class="prayer-box top"
                            :class="getPrayerClass(day, 'dhuha')"
                            :title="getPrayerTooltip(day, 'dhuha')"
                        ></div>
                        <div 
                            class="prayer-box bottom"
                            :class="getPrayerClass(day, 'dhuhur')"
                            :title="getPrayerTooltip(day, 'dhuhur')"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="legend">
                <div class="legend-title">Keterangan:</div>
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-box sholat"></div>
                        <span>Alpa Dhuha / Alpa Zuhur (Sholat)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box halangan"></div>
                        <span>Haid / Sakit (Halangan)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box tidak-hadir"></div>
                        <span>Tidak Hadir / Tidak Sholat</span>
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
/* Mobile-first styles */
.calendar-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.calendar-header {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.month-selector {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.day-cell {
    aspect-ratio: 1;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 2px;
    display: flex;
    flex-direction: column;
    background: white;
}

.day-number {
    font-size: 0.625rem;
    font-weight: 600;
    color: #374151;
    text-align: center;
    margin-bottom: 2px;
}

.prayer-boxes {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.prayer-box {
    flex: 1;
    border-radius: 3px;
    transition: transform 0.2s;
    cursor: help;
    border: 1px solid #e5e7eb;
    min-height: 15px;
}

.prayer-box:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.prayer-box.sholat {
    background: #10b981 !important;
    border-color: #059669 !important;
}

.prayer-box.halangan {
    background: #ef4444 !important;
    border-color: #dc2626 !important;
}

.prayer-box.tidak-hadir {
    background: #d1d5db !important;
    border-color: #9ca3af !important;
}

.legend {
    background: #f9fafb;
    padding: 0.75rem;
    border-radius: 8px;
}

.legend-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
    font-size: 0.875rem;
}

.legend-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-box {
    width: 30px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.legend-box.sholat {
    background: #10b981 !important;
    border-color: #059669 !important;
}

.legend-box.halangan {
    background: #ef4444 !important;
    border-color: #dc2626 !important;
}

.legend-box.tidak-hadir {
    background: #d1d5db !important;
    border-color: #9ca3af !important;
}

.legend-item span {
    font-size: 0.75rem;
    color: #4b5563;
}

/* Tablet styles (768px and up) */
@media (min-width: 768px) {
    .calendar-container {
        gap: 1.5rem;
    }

    .calendar-header {
        gap: 1rem;
    }

    .loading-container {
        min-height: 400px;
    }

    .calendar-grid {
        gap: 6px;
    }

    .day-cell {
        border-radius: 8px;
        padding: 4px;
    }

    .day-number {
        font-size: 0.75rem;
        margin-bottom: 4px;
    }

    .prayer-box {
        border-radius: 4px;
        min-height: 20px;
    }

    .legend {
        padding: 1rem;
    }

    .legend-title {
        font-size: 1rem;
        margin-bottom: 0.75rem;
    }

    .legend-item {
        gap: 0.75rem;
    }

    .legend-box {
        width: 40px;
        height: 24px;
    }

    .legend-item span {
        font-size: 0.875rem;
    }
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .calendar-grid {
        gap: 8px;
    }
}

/* Dialog responsive overrides */
:deep(.p-dialog) {
    width: 95vw !important;
    max-width: 800px !important;
}

:deep(.p-dialog .p-dialog-header) {
    padding: 1rem;
    font-size: 1rem;
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1rem;
}

@media (min-width: 768px) {
    :deep(.p-dialog .p-dialog-header) {
        padding: 1.25rem;
        font-size: 1.125rem;
    }

    :deep(.p-dialog .p-dialog-content) {
        padding: 1.5rem;
    }
}
</style>