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

// Üst bardaki hızlı hasta araması: seçilen hastanın kartına gider.
Alpine.data('quickSearch', (searchUrl, url) => ({
    url,
    query: '',
    results: [],
    open: false,
    active: 0,

    async search() {
        if (this.query.trim().length < 2) {
            this.results = [];
            this.open = false;
            return;
        }

        const { data } = await window.axios.get(searchUrl, { params: { q: this.query } });
        this.results = data;
        this.active = 0;
        this.open = true;
    },

    move(step) {
        if (this.results.length) {
            this.active = (this.active + step + this.results.length) % this.results.length;
        }
    },

    go() {
        const patient = this.results[this.active];
        if (patient) {
            window.location.href = `${this.url}/${patient.id}`;
        }
    },
}));

Alpine.start();
