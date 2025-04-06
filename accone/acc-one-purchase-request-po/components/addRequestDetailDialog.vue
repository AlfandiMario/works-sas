<template>
  <v-layout row justify-center>
    <v-dialog v-model="dialogDetail" persistent max-width="500px">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          FORM REQUEST DETAIL
        </v-card-title>
        <v-card-text class="pt-0 pb-0">
          <v-form ref="formDetail" v-model="validationDetail" lazy-validtion>
            <v-layout wrap>
              <v-flex xs12>
                <v-text-field
                  v-model="xDescriptionDetail"
                  label="Deskripsi"
                  :rules="descriptionRules"
                  required
                ></v-text-field>
              </v-flex>
              <v-flex xs12>
                <v-text-field
                  v-model="xAmountRequest"
                  type="number"
                  label="Jumlah"
                  :min="1"
                  :rules="amountRules"
                  required
                ></v-text-field>
              </v-flex>
              <v-flex xs12>
                <v-text-field
                  v-model="xEstimationPrice"
                  label="Harga"
                  type="number"
                  :min="1"
                  prefix="Rp "
                  :rules="priceRules"
                  required
                ></v-text-field>
              </v-flex>
            </v-layout>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" flat @click="closeDialogDetail()"> Tutup </v-btn>
          <v-btn v-if="act === 'new'" color="primary" dark @click="saveDetail()"
            >Simpan</v-btn
          >
          <v-btn
            v-if="act === 'edit'"
            color="primary"
            dark
            @click="updateDetail()"
            >Simpan Perubahan</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-layout>
</template>

<script>
module.exports = {
  name: "AddRequestDetailDialog",
  data() {
    return {
      descriptionRules: [(v) => !!v || "Deskripsi harus diisi"],
      amountRules: [(v) => (!!v && v > 0) || "Jumlah harus diisi"],
      priceRules: [(v) => (!!v && v > 0) || "Harga harus diisi"],
      validationDetail: false,
    };
  },
  computed: {
    selectedMainTable() {
      return this.$store.state.requester.selectedMainTable;
    },
    selectedDetailTable() {
      return this.$store.state.requester.selectedDetailTable;
    },
    xDescriptionDetail: {
      get() {
        return this.$store.state.requester.xDescriptionDetail;
      },
      set(val) {
        this.$store.commit("requester/updateXDescriptionDetail", val);
      },
    },
    xAmountRequest: {
      get() {
        return this.$store.state.requester.xAmountRequest;
      },
      set(val) {
        this.$store.commit("requester/updateXAmountRequest", val);
      },
    },
    xEstimationPrice: {
      get() {
        return this.$store.state.requester.xEstimationPrice;
      },
      set(val) {
        this.$store.commit("requester/updateXEstimationPrice", val);
      },
    },
    dialogDetail: {
      get() {
        return this.$store.state.requester.dialogDetail;
      },
      set(val) {
        this.$store.commit("requester/updateDialogDetail", val);
      },
    },
    act() {
      return this.$store.state.requester.act;
    },
  },
  methods: {
    closeDialogDetail() {
      this.$store.commit("requester/updateXDescriptionDetail", "");
      this.$store.commit("requester/updateXAmountRequest", 0);
      this.$store.commit("requester/updateXEstimationPrice", 0);
      this.$refs.formDetail.resetValidation();
      this.$store.commit("requester/updateDialogDetail", false);
    },
    saveDetail() {
      if (this.$refs.formDetail.validate()) {
        this.$store.dispatch("requester/saveDetail", {
          PRID: this.selectedMainTable.PurchaseRequestDirectID,
          PRDDescriptionDetail: this.xDescriptionDetail,
          PRDAmountRequest: this.xAmountRequest,
          PRDEstimationPrice: this.xEstimationPrice,
        });
      }
    },
    updateDetail() {
      if (this.$refs.formDetail.validate()) {
        this.$store.dispatch("requester/updateDetail", {
          PRID: this.selectedMainTable.PurchaseRequestDirectID,
          PRDID: this.selectedDetailTable.PurchaseRequestDirectDetailID,
          PRDDescriptionDetail: this.xDescriptionDetail,
          PRDAmountRequest: this.xAmountRequest,
          PRDEstimationPrice: this.xEstimationPrice,
        });
      }
    },
  },
  watch: {
    dialogDetail(val, old) {
      if (val) {
        this.$refs.formDetail.reset();
        this.$refs.formDetail.resetValidation();
      }
    },
    xAmountRequest(val, old) {
      if (val <= 0) {
        this.$store.commit("requester/updateXAmountRequest", 0);
      }
    },
    xEstimationPrice(val, old) {
      if (val < 0) {
        this.$store.commit("requester/updateXEstimationPrice", 0);
      }
    },
  },
};
</script>
