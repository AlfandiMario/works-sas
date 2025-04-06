<template>
  <v-layout row justify-center>
    <v-dialog v-model="dialogRequest" persistent max-width="50vw">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          FORM PURCHASE REQUEST PO
        </v-card-title>
        <v-card-text class="pt-2 pb-0">
          <v-form ref="formRequest" v-model="validationForm" lazy-validtion>
            <v-layout wrap>
              <v-flex xs12>
                <v-menu
                  v-model="menuDateUse"
                  :close-on-content-click="false"
                  transition="scale-transition"
                  :nudge-right="40"
                  lazy
                  offset-y
                  full-width
                  max-width="290px"
                  min-width="290px"
                >
                  <template v-slot:activator="{ on }">
                    <v-text-field
                      :value="dateUseFormatted"
                      label="Tanggal Pelaksanaan"
                      readonly
                      v-on="on"
                      @blur="dDateUse = deFormatedDate(dateUseFormatted)"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    no-title
                    v-model="xDateUse"
                    @input="menuDateUse = false"
                  ></v-date-picker>
                </v-menu>
              </v-flex>
              <v-flex xs12>
                <v-text-field
                  v-model="xNoReferensi"
                  label="No Referensi"
                ></v-text-field>
              </v-flex>
              <v-flex xs12>
                <v-autocomplete
                  v-model="xVendor"
                  label="Vendor"
                  menu-icon="mdi-chevron-down"
                  :items="vendorOptions"
                  item-text="SupplierName"
                  item-value="SupplierID"
                  :rules="vendorRules"
                  required
                ></v-autocomplete>
              </v-flex>
              <v-flex xs12>
                <v-autocomplete
                  v-model="xItemType"
                  label="Tipe"
                  menu-icon="mdi-chevron-down"
                  :items="itemTypeOptions"
                  item-text="ItemTypeName"
                  item-value="ItemTypeID"
                  :rules="itemTypeRules"
                  required
                ></v-autocomplete>
              </v-flex>
              <v-flex xs12>
                <v-autocomplete
                  v-model="xRegional"
                  label="Reional"
                  menu-icon="mdi-chevron-down"
                  :items="regionalOptions"
                  item-text="S_RegionalName"
                  item-value="S_RegionalID"
                  :rules="regionalRules"
                  required
                ></v-autocomplete>
              </v-flex>
              <v-flex xs12>
                <v-autocomplete
                  v-model="xBranch"
                  label="Cabang"
                  menu-icon="mdi-chevron-down"
                  :items="branchOptions"
                  item-text="M_BranchName"
                  item-value="M_BranchCode"
                ></v-autocomplete>
              </v-flex>
              <v-flex xs12>
                <v-text-field
                  v-model="xDescription"
                  label="Keterangan"
                ></v-text-field>
              </v-flex>
            </v-layout>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" flat @click="closeDialogRequest()">
            Tutup
          </v-btn>
          <v-btn
            v-if="act === 'new'"
            color="primary"
            dark
            @click="saveRequest()"
            >Simpan</v-btn
          >
          <v-btn
            v-if="act === 'edit'"
            color="primary"
            dark
            @click="updateRequest()"
            >Simpan Perubahan</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-layout>
</template>

<script>
module.exports = {
  name: "AddPurchaseRequestDialog",
  data() {
    return {
      vendorRules: [(v) => !!v || "Vendor harus diisi"],
      itemTypeRules: [(v) => !!v || "Tipe harus diisi"],
      regionalRules: [(v) => !!v || "Regional harus diisi"],
      branchRules: [(v) => !!v || "Cabang harus diisi"],
      validationForm: false,
    };
  },
  computed: {
    user() {
      return this.$store.state.requester.user;
    },
    dialogRequest: {
      get() {
        return this.$store.state.requester.dialogRequest;
      },
      set(val) {
        this.$store.commit("requester/updateDialogRequest", val);
      },
    },
    menuDateUse: {
      get() {
        return this.$store.state.requester.menuDateUse;
      },
      set(val) {
        this.$store.commit("requester/updateMenuDateUse", val);
      },
    },
    dateUseFormatted() {
      return this.formatDate(this.xDateUse);
    },
    dDateUse: {
      get() {
        return this.$store.state.requester.dDateUse;
      },
      set(val) {
        this.$store.commit("requester/updateDDateUse", val);
      },
    },
    xDateUse: {
      get() {
        return this.$store.state.requester.xDateUse;
      },
      set(val) {
        this.$store.commit("requester/updateXDateUse", val);
      },
    },
    xNoReferensi: {
      get() {
        return this.$store.state.requester.xNoReferensi;
      },
      set(val) {
        this.$store.commit("requester/updateXNoReferensi", val);
      },
    },
    xRegional: {
      get() {
        return this.$store.state.requester.xRegional;
      },
      set(val) {
        this.$store.commit("requester/updateXRegional", val);
      },
    },
    xBranch: {
      get() {
        return this.$store.state.requester.xBranch;
      },
      set(val) {
        this.$store.commit("requester/updateXBranch", val);
      },
    },
    xVendor: {
      get() {
        return this.$store.state.requester.xVendor;
      },
      set(val) {
        this.$store.commit("requester/updateXVendor", val);
      },
    },
    xItemType: {
      get() {
        return this.$store.state.requester.xItemType;
      },
      set(val) {
        this.$store.commit("requester/updateXItemType", val);
      },
    },
    vendorOptions() {
      return this.$store.state.requester.vendorOptions;
    },
    itemTypeOptions() {
      return this.$store.state.requester.itemTypeOptions;
    },
    regionalOptions() {
      return this.$store.state.requester.regionalOptions;
    },
    branchOptions() {
      return this.$store.state.requester.branchOptions;
    },
    xDescription: {
      get() {
        return this.$store.state.requester.xDescription;
      },
      set(val) {
        this.$store.commit("requester/updateXDescription", val);
      },
    },
    xPRID() {
      return this.$store.state.requester.xPRID;
    },
    act() {
      return this.$store.state.requester.act;
    },
  },
  methods: {
    formatDate(date) {
      if (!date) return null;

      const [year, month, day] = date.split("-");
      return `${day}-${month}-${year}`;
    },
    deFormatedDate(date) {
      if (!date) return null;

      const [day, month, year] = date.split("-");
      return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
    },
    closeDialogRequest() {
      this.$store.commit(
        "requester/updateXDateUse",
        moment(new Date()).format("YYYY-MM-DD")
      );
      this.$store.commit("requester/updateXVendor", "");
      this.$store.commit("requester/updateXItemType", "");
      this.$store.commit("requester/updateXRegional", "");
      this.$store.commit("requester/updateXBranch", "");
      this.$store.commit("requester/updateXDescription", "");
      this.$refs.formRequest.resetValidation();
      this.$store.commit("requester/updateDialogRequest", false);
    },
    saveRequest() {
      if (this.$refs.formRequest.validate()) {
        this.$store.dispatch("requester/saveRequest", {
          PRDateUse: this.xDateUse,
          PRBranch: this.xBranch,
          PRDescription: this.xDescription,
        });
      }
    },
    updateRequest() {
      if (this.$refs.formRequest.validate()) {
        this.$store.dispatch("requester/updateRequest", {
          PRID: this.xPRID,
          PRDateUse: this.xDateUse,
          PRBranch: this.xBranch,
          PRDescription: this.xDescription,
        });
      }
    },
    thrRegional: _.debounce(function () {
        this.$store.dispatch("requester/getBranch")
    }, 1000),
  },
  watch: {
    xRegional(val, old) {
      this.xRegional = val
      this.thrRegional()
    }
  }
};
</script>
