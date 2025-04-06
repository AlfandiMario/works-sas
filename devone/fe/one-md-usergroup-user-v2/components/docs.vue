<template>
    <!-- Berisi kode komponen yang akan ditampilkan -->
</template>

<style scoped>
/* Berisi embedded CSS Tamabahan jika diperlukan */
</style>

<script>
module.exports = {
    data: () => ({
        // Berisi deklarasi yang akan digunakan pada komponen
        // Biasanya data yang tidak perlu ditambahkan di state (jarang berubah)
        // Contoh: title: 'Hello World'
    }),
    mounted() {
        // Berisi kode yang akan dijalankan langsung setelah komponen dimuat di web
    },
    computed: {
        // Berisi fungsi yang akan mengembalikan nilai berdasarkan data yang ada
        // Contoh: titleUppercase() { return this.title.toUpperCase() }
        // Biasa digunakan untuk mengubah data/state sebelum ditampilkan
    },
    methods: {
        // Berisi fungsi yang akan dijalankan ketika dipanggil
        // Contoh: changeTitle() { this.title = 'Hello Vue' }
    },
    watch: {
        // Berisi fungsi yang akan dijalankan secara otomatis ketika data/state berubah
        // Contoh: title() { console.log('Title changed') }
    }
}
</script>