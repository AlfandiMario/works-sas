<template>
    <v-layout row justify-center>
        <v-dialog v-model="dialogRequest" persistent max-width="50vw">
            <v-card>
                <v-card-title class="headline grey lighten-2" primary-title>
                    FORM REQUEST TRANSFER
                </v-card-title>
                <v-card-text class="pt-2 pb-0">
                    <v-form ref="formRequest" v-model="validationForm" lazy-validation>
                        <v-layout wrap>
                            <v-flex xs12>
                                <v-menu v-model="menuDateUse" :close-on-content-click="false"
                                    transition="scale-transition" :nudge-right="40" lazy offset-y full-width
                                    max-width="290px" min-width="290px">
                                    <template v-slot:activator="{ on }">
                                        <v-text-field :value="dateUseFormatted" label="Tanggal Pelaksanaan" readonly
                                            v-on="on"
                                            @blur="dDateUse = deFormatedDate(dateUseFormatted)"></v-text-field>
                                    </template>
                                    <v-date-picker no-title v-model="xDateUse"
                                        @input="menuDateUse = false"></v-date-picker>
                                </v-menu>
                            </v-flex>
                            <v-flex xs12>
                                <v-autocomplete v-model="xBranch" label="Cabang" menu-icon="mdi-chevron-down"
                                    :items="branchOptions" item-text="M_BranchName" item-value="M_BranchCode"
                                    :rules="noEmptyRule"></v-autocomplete>
                            </v-flex>
                            <v-flex xs12>
                                <v-text-field v-model="xDescription" label="Keterangan"
                                    :rules="noEmptyRule"></v-text-field>
                            </v-flex>
                        </v-layout>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="error" flat @click="closeDialogRequest()">
                        Tutup
                    </v-btn>
                    <v-btn v-if="act === 'new' && validationForm == true" color="primary" dark
                        @click="saveRequest()">Simpan</v-btn>
                    <v-btn v-if="act === 'edit' && validationForm == true" color="primary" dark
                        @click="updateRequest()">Simpan
                        Perubahan</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-layout>
</template>

<script>
module.exports = {
    name: "AddTransferRequestDialog",
    data() {
        return {
            noEmptyRule: [(v) => !!v || "Cabang harus diisi"],
            validationForm: false,
        };
    },
    computed: {
        user() {
            return this.$store.state.transfer.user;
        },
        dialogRequest: {
            get() {
                return this.$store.state.transfer.dialogRequest;
            },
            set(val) {
                this.$store.commit("transfer/updateDialogRequest", val);
            },
        },
        menuDateUse: {
            get() {
                return this.$store.state.transfer.menuDateUse;
            },
            set(val) {
                this.$store.commit("transfer/updateMenuDateUse", val);
            },
        },
        dateUseFormatted() {
            return this.formatDate(this.xDateUse);
        },
        dDateUse: {
            get() {
                return this.$store.state.transfer.dDateUse;
            },
            set(val) {
                this.$store.commit("transfer/updateDDateUse", val);
            },
        },
        xDateUse: {
            get() {
                return this.$store.state.transfer.xDateUse;
            },
            set(val) {
                this.$store.commit("transfer/updateXDateUse", val);
            },
        },
        xNoReferensi: {
            get() {
                return this.$store.state.transfer.xNoReferensi;
            },
            set(val) {
                this.$store.commit("transfer/updateXNoReferensi", val);
            },
        },
        xRegional: {
            get() {
                return this.$store.state.transfer.xRegional;
            },
            set(val) {
                this.$store.commit("transfer/updateXRegional", val);
            },
        },
        xBranch: {
            get() {
                return this.$store.state.transfer.xBranch;
            },
            set(val) {
                this.$store.commit("transfer/updateXBranch", val);
            },
        },
        xVendor: {
            get() {
                return this.$store.state.transfer.xVendor;
            },
            set(val) {
                this.$store.commit("transfer/updateXVendor", val);
            },
        },
        xItemType: {
            get() {
                return this.$store.state.transfer.xItemType;
            },
            set(val) {
                this.$store.commit("transfer/updateXItemType", val);
            },
        },
        vendorOptions() {
            return this.$store.state.transfer.vendorOptions;
        },
        itemTypeOptions() {
            return this.$store.state.transfer.itemTypeOptions;
        },
        regionalOptions() {
            return this.$store.state.transfer.regionalOptions;
        },
        branchOptions() {
            return this.$store.state.transfer.branchOptions;
        },
        xDescription: {
            get() {
                return this.$store.state.transfer.xDescription;
            },
            set(val) {
                this.$store.commit("transfer/updateXDescription", val);
            },
        },
        xPRID() {
            return this.$store.state.transfer.xPRID;
        },
        act() {
            return this.$store.state.transfer.act;
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
                "transfer/updateXDateUse",
                moment(new Date()).format("YYYY-MM-DD")
            );
            this.$store.commit("transfer/updateXRegional", "");
            this.$store.commit("transfer/updateXBranch", "");
            this.$store.commit("transfer/updateXDescription", "");
            this.$refs.formRequest.resetValidation();
            this.$store.commit("transfer/updateDialogRequest", false);
        },
        saveRequest() {
            if (this.validationForm) {
                console.log("save request 2");
                this.$store.dispatch("transfer/saveRequest", {
                    S_RegionalID: this.user.S_RegionalID,
                    M_BranchCode: this.xBranch,
                    GoodsTfNotes: this.xDescription,
                    GoodsTfDate: this.xDateUse,
                    GoodsTfStatus: "Draft" // Draft. Pertama kali dibuat
                });
                console.log("validationf form", this.validationForm);
            }
        },
        updateRequest() {
            if (this.$refs.formRequest.validate()) {
                this.$store.dispatch("transfer/updateRequest", {
                    PRID: this.xPRID,
                    PRDateUse: this.xDateUse,
                    PRBranch: this.xBranch,
                    PRDescription: this.xDescription,
                });
            }
        },
        thrRegional: _.debounce(function () {
            this.$store.dispatch("transfer/getBranch")
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
