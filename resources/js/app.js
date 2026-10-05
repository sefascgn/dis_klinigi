import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Formlardaki hasta seçici (resources/views/components/patient-picker.blade.php)
Alpine.data('patientPicker', (initial, searchUrl) => ({
    selected: initial,
    query: '',
    results: [],
    open: false,
    loading: false,

    async search() {
        if (this.query.trim().length < 2) {
            this.results = [];
            this.open = false;
            return;
        }

        this.loading = true;
        try {
            const { data } = await window.axios.get(searchUrl, { params: { q: this.query } });
            this.results = data;
            this.open = true;
        } finally {
            this.loading = false;
        }
    },

    choose(patient) {
        this.selected = patient;
        this.open = false;
        this.query = '';
        this.$dispatch('patient-selected', patient);
    },

    clear() {
        this.selected = null;
        this.$nextTick(() => this.$root.querySelector('input[type=text]')?.focus());
    },
}));

Alpine.start();
