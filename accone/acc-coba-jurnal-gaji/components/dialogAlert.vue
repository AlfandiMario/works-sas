<template>
  <div style="width: 100%;" class="">
    <v-dialog v-model="dialogAlert" :name="name" persistent width="500">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Konfirmasi
        </v-card-title>

        <v-card-text class="pa-2">
          {{ msg }}
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            :loading="loading"
            :disabled="loading"
            flat
            @click="dialogAlert = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            :loading="loading"
            :disabled="loading"
            @click="confirm"
          >
            Yakin
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<style scoped></style>

<script>
  module.exports = {
    mounted() {
      //   this.formatCurrency(10000);
      //   this.$store.dispatch("balance/getPeriode");
    },
    props: {
      msg: {
        type: String,
        required: true,
      },
      // Default values
      loading: {
        type: Boolean,
        default: false,
      },
      confirm: {
        type: Function,
        default: function () {},
      },
      name: {
        type: String,
        default: "",
      },
    },
    data: () => ({
      loadingCsv: false,
      dataUpload: [],
      debit: 0,
      credit: 0,
      description: "",
      act: "add",
      selectedDetail: {},
      headers: [
        {
          text: "NO. ACCOUNT",
          align: "left",
          sortable: false,
          value: "action",
          width: "10%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "KETERANGAN",
          align: "left",
          sortable: false,
          value: "mr",
          width: "40%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "DEBIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "KREDIT",
          align: "left",
          sortable: false,
          value: "lab",
          width: "20%",
          class: "blue lighten-3 white--text",
        },
        {
          text: "AKSI",
          align: "left",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "blue lighten-3 white--text",
        },
      ],
    }),
    computed: {
      dialogAlert: {
        get() {
          return this.$store.state.recrusive.dialogAlert;
        },
        set(val) {
          this.$store.commit("recrusive/update_dialogAlert", val);
        },
      },
    },
    methods: {},
    watch: {
      searchCoa(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        this.$store.dispatch("preset/searchCoa");
      },

      search_city(val, old) {
        if (val == old) return;
        if (!val) return;
        if (val.length < 1) return;
        if (this.$store.state.patient.update_autocomplete_status == 1) return;
        this.thr_search_city();
      },
    },
  };
</script>
