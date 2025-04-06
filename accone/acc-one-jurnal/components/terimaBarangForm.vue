<template>
    <div>
        <!-- Dialog Besar (Head) -->
        <v-dialog persistent v-model="dialogJurnalTerimaBarang" width="70vw">
            <v-card>
                <v-card-title class="headline grey lighten-2" primary-title>
                    FORM JURNAL PENERIMAAN BARANG
                </v-card-title>

                <v-card-text>
                    <v-layout row wrap>
                        <v-flex xs6 class="px-1 mb-2">
                            <v-text-field label="COMPANY"
                                :value="xact === 'edit' ? selectedJurnal.M_BranchCompanyName : user.M_BranchCompanyName"
                                readonly hide-details outline></v-text-field>
                        </v-flex>
                        <v-flex xs6 class="px-1 mb-2">
                            <v-text-field :value="selectedJurnalType.JurnalTypeName" label="JURNAL TYPE" readonly
                                hide-details outline></v-text-field>
                            <p v-if="checkErrorFormJurnal('requireitemjurnaltype')" class="error pl-2 pr-2"
                                style="color:#fff">Pilih salah satu tipe jurnal</p>
                        </v-flex>
                        <v-flex xs6 class="px-1 mb-2">
                            <v-text-field label="REGIONAL"
                                :value="xact === 'edit' ? selectedJurnal.S_RegionalName : user.S_RegionalName" readonly
                                hide-details outline></v-text-field>
                        </v-flex>
                        <v-flex xs3 class="px-1 mb-2">
                            <v-menu v-model="menuFormSelectedDate" :close-on-content-click="false" :nudge-right="40"
                                lazy transition="scale-transition" offset-y full-width max-width="290px"
                                min-width="290px">
                                <template v-slot:activator="{ on }">
                                    <v-text-field class="mr-2" v-model="formatedSelectedDate" label="TANGGAL" outline
                                        hide-details readonly v-on="on"
                                        @blur="deFormatedDate(formatedSelectedDate)"></v-text-field>
                                </template>
                                <v-date-picker v-model="selectedDate" no-title
                                    @input="menuFormSelectedDate = false"></v-date-picker>
                            </v-menu>
                            <p v-if="checkErrorFormJurnal('requireitemdate')" class="error pl-2 pr-2"
                                style="color:#fff">Pilih tanggal jurnal</p>
                            <p v-if="checkErrorFormJurnal('dateoutofperiode')" class=" error pl-2 pr-2"
                                style="color:#fff">
                                Tanggal di luar rentang periode terpilih
                            </p>
                        </v-flex>
                        <v-flex xs3 class="px-1 mb-2">
                            <v-autocomplete :search-input.sync="searchPeriode" v-model="selectedPeriode"
                                :items="periodeList" item-text="displayPeriode" :loading="loadingAutocomplete"
                                hide-no-data hide-selected label="PERIODE" hide-details outline return-object required>
                                <template slot="item" slot-scope="{ item }">
                                    <v-list-tile-content>
                                        <v-list-tile-title v-text="item.displayPeriode"></v-list-tile-title>
                                        <v-list-tile-sub-title v-text="item.periode"></v-list-tile-sub-title>
                                    </v-list-tile-content>
                                </template>
                            </v-autocomplete>
                            <p v-if="checkErrorFormJurnal('requireitemperiode')" class="error pl-2 pr-2"
                                style="color:#fff">Periode harus
                                dipilih</p>
                        </v-flex>
                        <v-flex xs6 class="px-1 mb-2">
                            <v-text-field label="BRANCH"
                                :value="xact === 'edit' ? selectedJurnal.M_BranchName : user.M_BranchName" readonly
                                hide-details outline></v-text-field>
                        </v-flex>
                        <v-flex xs6 class="px-1 mb-2">
                            <v-text-field label="JUDUL JURNAL" v-model="jurnalTitle" hide-details outline
                                required></v-text-field>
                            <p v-if="checkErrorFormJurnal('requireitemtitle')" class="error pl-2 pr-2"
                                style="color:#fff">Judul harus
                                diisi</p>
                        </v-flex>
                        <v-flex xs4 class="px-1 mb-2">
                            <v-autocomplete :search-input.sync="searchSupplier" v-model="selectedSupplier"
                                :items="supplierList" item-text="displaySupplier" :loading="loadingAutocomplete"
                                label="SUPPLIER" hide-no-data hide-selected hide-details outline return-object required>
                            </v-autocomplete>
                            <p v-if="checkErrorFormJurnal('requireitemsupplier')" class="error pl-2 pr-2"
                                style="color:#fff">Supplier harus
                                dipilih</p>
                        </v-flex>
                        <v-flex xs4 class="px-1 mb-2">
                            <v-text-field label="GRNI No." v-model="grniValue" :disabled="invoiceValue !== ''"
                                :required="invoiceValue === ''" @input="handleInputInvGrni('grniValue')"></v-text-field>
                        </v-flex>
                        <v-flex xs4 class="px-1 mb-2">
                            <v-text-field label="INVOICE No." v-model="invoiceValue" :disabled="grniValue !== ''"
                                :required="grniValue === ''" @input="handleInputInvGrni('invoiceValue')"></v-text-field>
                            <p v-if="checkErrorFormJurnal('requireitemgrniinvoice')" class="error pl-2 pr-2"
                                style="color:#fff">
                                Isi salah satu GRNI atau Invoice
                            </p>
                        </v-flex>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-text-field label="DESKRIPSI JURNAL" v-model="jurnalDescription" hide-details outline
                                required></v-text-field>
                            <p v-if="checkErrorFormJurnal('requireitemdescription')" class="error pl-2 pr-2"
                                style="color:#fff">Deskripsi
                                harus diisi</p>
                        </v-flex>
                        <v-flex xs12 class="px-1 mb-2" text-xs-right>
                            <v-btn small color="info" @click="openDialogAddDetail()">TAMBAH DETAIL</v-btn>
                        </v-flex>

                        <v-flex xs12 class="px-1 mb-2 mt-2">
                            <v-data-table :headers="detailHeader" :items="detailsJurnalTerimaBarang" hide-actions
                                class="elevation-1">
                                <template v-slot:items="props">
                                    <td class="px-1 text-xs-center">{{ props.item.coaAccountNo }}</td>
                                    <td class="px-1 text-xs-center">{{ props.item.coaDescription }}</td>
                                    <td class="px-1 text-xs-right">{{ formatCurrency(props.item.jurnalTxDebit)
                                        }}
                                    </td>
                                    <td class="px-1 text-xs-right">{{ formatCurrency(props.item.jurnalTxCredit)
                                        }}
                                    </td>
                                    <td class="px-1 text-xs-center"><v-icon small
                                            @click="deleteDetailTerimaBarang(props.item)">delete</v-icon></td>
                                </template>
                                <template v-slot:footer>
                                    <tr>
                                        <td colspan="2" class="font-weight-bold px-1">Total</td>
                                        <td class="font-weight-bold px-1 text-xs-right">
                                            {{ formatCurrency(detailSum.debit) }}
                                        </td>
                                        <td class="font-weight-bold px-1 text-xs-right">
                                            {{ formatCurrency(detailSum.credit) }}
                                        </td>
                                        <td class="font-weight-bold px-1"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="font-weight-bold px-1">
                                            Balance
                                        </td>
                                        <td class="text-right font-weight-bold px-1 text-xs-right">
                                            {{ formatCurrency(detailSum.balance) }}
                                        </td>
                                        <td class="font-weight-bold px-1"></td>
                                    </tr>
                                    <tr v-if="checkErrorFormJurnal('balanceerror')">
                                        <td colspan="5" class="text-xs-right">
                                            <p class="error pl-2 pr-2" style="color:#fff">
                                                Balance harus 0 sebelum disimpan. Silakan edit debit/kredit
                                            </p>
                                            <p class="error pl-2 pr-2" style="color:#fff">
                                                Hanya boleh ada 1 kredit di Jurnal Penerimaan Barang
                                            </p>
                                        </td>
                                    </tr>
                                </template>
                            </v-data-table>
                        </v-flex>
                    </v-layout>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="error" flat @click="closeDialog()">
                        TUTUP
                    </v-btn>
                    <v-btn v-if="xact === 'add'" color="primary" @click="saveJurnalTerimaBarang()">SIMPAN</v-btn>
                    <v-btn v-if="xact === 'edit'" :disabled="this.$store.state.terimaBarang.isPosted === 'Y'"
                        color="primary" @click="saveEditJurnalTerimaBarang()">SIMPAN PERUBAHAN</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog Kecil (Detail) -->
        <v-dialog v-model="dialogAddDetail" persistent width="500">
            <v-card>
                <v-card-title class="headline grey lighten-2" primary-title>
                    FORM DETAIL PENERIMAAN BARANG
                </v-card-title>
                <v-card-text>
                    <v-layout wrap>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-autocomplete label="No. CoA" :items="coaList" v-model="selectedCoa" hide-no-data
                                :search-input.sync="searchCoa" hide-details auto-select-first no-filter
                                item-text="coaDescription" outline return-object :loading="loadingAutocomplete"
                                no-data-text="Pilih Coa" clearable>
                                <template slot="item" slot-scope="{ item }">
                                    <v-list-tile-content>
                                        <v-list-tile-title v-text="item.coaDescription"></v-list-tile-title>
                                        <v-list-tile-sub-title v-text="item.coaAccountNo"></v-list-tile-sub-title>
                                    </v-list-tile-content>
                                </template>
                            </v-autocomplete>
                            <p v-if="checkErrorFormDetail('requireitemcoa')" class="error pl-2 pr-2" style="color:#fff">
                                CoA harus dipilih
                            </p>
                        </v-flex>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-text-field label="Debit" v-model="debitValue" :disabled="kreditValue !== 0"
                                :required="kreditValue === 0" @input="handleInputDetail('debitValue')" type="number"
                                @focus="clearDebit" @blur="resetDebit"></v-text-field>
                        </v-flex>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-text-field label="Kredit" v-model="kreditValue" :disabled="debitValue !== 0"
                                :required="kreditValue === 0" @input="handleInputDetail('kreditValue')" type="number"
                                @focus="clearCredit" @blur="resetCredit"></v-text-field>
                        </v-flex>
                        <v-flex xs-12 class="px-1 mb-2">
                            <p v-if="checkErrorFormDetail('requireitemdebitkredit')" class="error pl-2 pr-2"
                                style="color:#fff">
                                Isi salah satu Debit / Kredit
                            </p>
                        </v-flex>
                    </v-layout>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="secondary" flat @click="closeDialogAddDetail()">
                        TUTUP
                    </v-btn>
                    <v-btn color="primary" @click="addDetailTerimaBarang()">SIMPAN</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

    </div>
</template>

<style scoped></style>

<script>
module.exports = {
    name: "JurnalPenerimaanBarang",
    props: {
        show: {
            type: Boolean,
            default: false,
        },
    },
    components: {},
    mounted() {
        this.$store.dispatch("terimaBarang/getJurnalType");
        this.$store.dispatch("terimaBarang/getPeriode");
        this.$store.dispatch("terimaBarang/getSupplier");
        this.$store.dispatch("terimaBarang/getCoa");
    },
    data: () => ({
        menuFormSelectedDate: false,
        dialogAddDetail: false,
        detailHeader: [
            {
                text: "AKUN",
                align: "center",
                sortable: false,
                value: "mr",
                width: "15%",
                class: "pa-2 blue lighten-3 white--text",
            },
            {
                text: "DESKRIPSI",
                align: "center",
                sortable: false,
                value: "lab",
                width: "35%",
                class: "pa-2 blue lighten-3 white--text",
            },
            {
                text: "DEBET",
                align: "center",
                sortable: false,
                value: "lab",
                width: "20%",
                class: "pa-2 blue lighten-3 white--text",
            },
            {
                text: "KREDIT",
                align: "center",
                sortable: false,
                value: "lab",
                width: "20%",
                class: "pa-2  blue lighten-3 white--text",
            },
            {
                text: "AKSI",
                align: "center",
                sortable: false,
                value: "lab",
                width: "15%",
                class: "pa-2  blue lighten-3 white--text",
            }
        ]
    }),
    computed: {

        dialogJurnalTerimaBarang: {
            get() {
                return this.$store.state.terimaBarang.dialogJurnalTerimaBarang;
            },
            set(value) {
                this.$store.commit("terimaBarang/update_dialogJurnalTerimaBarang", value);
            },
        },

        user() {
            console.log("user", this.$store.state.terimaBarang.user);
            return this.$store.state.terimaBarang.user;
        },

        selectedJurnalType() {
            if (this.xact == 'edit') {
                return this.$store.state.terimaBarang.selectedJurnalType;
            } else {
                return this.$store.state.jurnalumum.x_drop_tipe_jurnal;
            }
        },

        jurnalTypeList() {
            return this.$store.state.terimaBarang.jurnalTypeList;
        },

        searchPeriode: {
            get() {
                return this.$store.state.terimaBarang.searchPeriode;
            },
            set(value) {
                this.$store.commit("terimaBarang/update_searchPeriode", value);
            }
        },
        selectedPeriode: {
            get() {
                return this.$store.state.terimaBarang.selectedPeriode;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_selectedPeriode", val);
            }
        },

        selectedDate: {
            get() {
                return this.$store.state.terimaBarang.selectedDate;
            },
            set(value) {
                this.$store.commit("terimaBarang/update_selectedDate", value);
            }
        },
        isSelectedDateValid() {
            return this.isDateInRange(this.selectedDate, this.selectedPeriode.periodeStartDate, this.selectedPeriode.periodeEndDate);
        },

        formatedSelectedDate() {
            return this.formatDate(this.selectedDate);
        },


        periodeList() {
            return this.$store.state.terimaBarang.periodeList;
        },

        loadingAutocomplete: {
            get() {
                return this.$store.state.terimaBarang.loadingAutocomplete;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_loadingAutocomplete", val);
            }
        },

        jurnalTitle: {
            get() {
                return this.$store.state.terimaBarang.jurnalTitle;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_jurnalTitle", val);
            }
        },

        searchSupplier: {
            get() {
                return this.$store.state.terimaBarang.searchSupplier;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_searchSupplier", val);
            }
        },
        selectedSupplier: {
            get() {
                return this.$store.state.terimaBarang.selectedSupplier;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_selectedSupplier", val);
            }
        },
        supplierList() {
            return this.$store.state.terimaBarang.supplierList;
        },

        grniValue: {
            get() {
                return this.$store.state.terimaBarang.grniValue;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_grniValue", val);
            }
        },
        invoiceValue: {
            get() {
                return this.$store.state.terimaBarang.invoiceValue;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_invoiceValue", val);
            }
        },

        jurnalDescription: {
            get() {
                return this.$store.state.terimaBarang.jurnalDescription;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_jurnalDescription", val);
            }
        },

        searchCoa: {
            get() {
                return this.$store.state.terimaBarang.searchCoa;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_searchCoa", val);
            }
        },
        coaList() {
            return this.$store.state.terimaBarang.coaList;
        },
        selectedCoa: {
            get() {
                return this.$store.state.terimaBarang.selectedCoa;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_selectedCoa", val);
            }
        },

        debitValue: {
            get() {
                return this.$store.state.terimaBarang.debitValue;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_debitValue", val);
            }
        },
        kreditValue: {
            get() {
                return this.$store.state.terimaBarang.kreditValue;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_kreditValue", val);
            }
        },
        detailsJurnalTerimaBarang: {
            get() {
                return this.$store.state.terimaBarang.detailsJurnalTerimaBarang;
            },
            set(val) {
                this.$store.commit("terimaBarang/update_detailsJurnalTerimaBarang", val);
            }
        },
        detailSum() {
            return this.$store.state.terimaBarang.detailSum;
        },

        xact: {
            get() {
                return this.$store.state.terimaBarang.act
            },
            set(val) {
                this.$store.commit("terimaBarang/update_actForm", val)
            }
        },

        selectedJurnal() {
            return this.$store.state.terimaBarang.selectedJurnal
        },

    },
    methods: {
        closeDialog() {
            this.$store.commit("terimaBarang/update_dialogJurnalTerimaBarang", false)
            this.$store.commit("terimaBarang/update_actForm", '')
            // reset all state in form
            this.$store.commit("terimaBarang/update_selectedJurnalType", {})
            this.$store.commit("terimaBarang/update_selectedPeriode", {})
            this.$store.commit("terimaBarang/update_selectedDate", '')
            this.$store.commit("terimaBarang/update_jurnalTitle", '')
            this.$store.commit("terimaBarang/update_selectedSupplier", {})
            this.$store.commit("terimaBarang/update_grniValue", '')
            this.$store.commit("terimaBarang/update_invoiceValue", '')
            this.$store.commit("terimaBarang/update_jurnalDescription", '')
            this.$store.commit("terimaBarang/update_detailsJurnalTerimaBarang", [])
            this.$store.commit("terimaBarang/update_detailSum", {
                debit: 0,
                credit: 0,
                balance: 0
            })
            this.$store.commit("jurnalumum/update_x_drop_tipe_jurnal", {})
        },
        isDateInRange(selectedDate, startDate, endDate) {
            if (!this.selectedPeriode) return true; // If periode is not selected, return true dulu

            if (!selectedDate || !startDate || !endDate) {
                return false; // If any date is missing, return false
            }
            return selectedDate >= startDate && selectedDate <= endDate;
        },
        formatDate(date) {
            if (!date) return null;

            const [year, month, day] = date.split("-");
            return `${day}/${month}/${year}`;
        },
        deFormatedDate(date) {
            if (!date) return null;

            const [day, month, year] = date.split("-");
            return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
        },
        handleInputInvGrni(input) {
            if (input === 'grniValue') {
                this.invoiceValue = ''; // Reset invoiceValue if grniValue is being inputed
            } else if (input === 'invoiceValue') {
                this.grniValue = ''; // Reset grniValue if invoiceValue is being inputed
            }
        },
        thr_search_periode: _.debounce(function () {
            this.$store.dispatch("terimaBarang/getPeriode", this.searchPeriode)
        }, 1000),
        thr_search_supplier: _.debounce(function () {
            this.$store.dispatch("terimaBarang/getSupplier", this.searchSupplier)
        }, 1000),

        openDialogAddDetail() {
            this.$store.commit("terimaBarang/update_errorsAddJurnal", []);
            var errTambah = this.$store.state.terimaBarang.errorsAddJurnal;
            if (_.isEmpty(this.selectedJurnalType)) {
                errTambah.push("requireitemjurnaltype")
            }
            if (_.isEmpty(this.selectedPeriode)) {
                errTambah.push("requireitemperiode")
            }
            if (_.isEmpty(this.selectedDate)) {
                errTambah.push("requireitemdate")
            }
            if (_.isEmpty(this.jurnalTitle)) {
                errTambah.push("requireitemtitle")
            }
            if (_.isEmpty(this.selectedSupplier)) {
                errTambah.push("requireitemsupplier")
            }
            if (_.isEmpty(this.grniValue) && _.isEmpty(this.invoiceValue)) {
                errTambah.push("requireitemgrniinvoice")
            }
            if (_.isEmpty(this.jurnalDescription)) {
                errTambah.push("requireitemdescription")
            }
            // Validate selectedDate in range periodeStartDate and periodeEndDate
            if (!this.isSelectedDateValid) {
                errTambah.push("dateoutofperiode")
            }

            if (errTambah.length === 0) {
                this.dialogAddDetail = true; // If there is no error, open dialog
            }
        },
        closeDialogAddDetail() {
            this.dialogAddDetail = false;
        },
        thr_searchCoa: _.debounce(function () {
            this.$store.dispatch("terimaBarang/getCoa", this.searchCoa);
        }, 2000),

        handleInputDetail(input) {
            if (input === 'debitValue') {
                this.kreditValue = 0; // Reset kreditValue if debitValue is being inputd
            } else if (input === 'kreditValue') {
                this.debitValue = 0; // Reset debitValue if kreditValue is being inputd
            }
        },
        clearDebit() {
            if (this.debitValue === 0) {
                this.debitValue = '';
            }
        },
        resetDebit() {
            if (this.debitValue === '' || this.debitValue === null) {
                this.debitValue = 0;
            }
        },
        clearCredit() {
            if (this.kreditValue === 0) {
                this.kreditValue = '';
            }
        },
        resetCredit() {
            if (this.kreditValue === '' || this.kreditValue === null) {
                this.kreditValue = 0;
            }
        },

        addDetailTerimaBarang() {
            this.$store.commit("terimaBarang/update_errorsAddDetail", []);
            var errDetail = this.$store.state.terimaBarang.errorsAddDetail;
            if (_.isEmpty(this.debitValue) && _.isEmpty(this.kreditValue)) {
                errDetail.push("requireitemdebitkredit")
            }
            if (_.isEmpty(this.selectedCoa)) {
                errDetail.push("requireitemcoa")
            }
            if (!_.isEmpty(this.debitValue) && !_.isEmpty(this.kreditValue)) {
                errDetail.push("requireitemdebitkredit")
            }

            if (errDetail.length === 0) {
                this.$store.commit("terimaBarang/update_errorsAddDetail", []);
                let detailsJurnalTerimaBarang = this.detailsJurnalTerimaBarang

                // Jika Debit
                if (_.isEmpty(this.kreditValue)) {
                    // TODO: Validate selectedCoa.coatype juga harus debit

                    detailsJurnalTerimaBarang.push({
                        coaID: this.selectedCoa.coaID,
                        coaDescription: this.selectedCoa.coaDescription,
                        jurnalTxDebit: this.debitValue,
                        jurnalTxCredit: this.kreditValue,
                        coaAccountNo: this.selectedCoa.coaAccountNo
                    })
                }
                // Jika Credit
                else if (_.isEmpty(this.debitValue)) {
                    // TODO: Validate selectedCoa.coatype juga harus credit

                    let addOns = {}
                    // Jika ada GRNI pakai grni value jika tidak pakai invoice value
                    if (!_.isEmpty(this.grniValue)) {
                        addOns = [
                            {
                                jurnalAddOnCode: "SUPCD",
                                jurnalAddOnValue: this.selectedSupplier.SupplierCode
                            },
                            {
                                jurnalAddOnCode: "GRNIGR",
                                jurnalAddOnValue: this.grniValue
                            }
                        ]
                    } else {
                        addOns = [
                            {
                                jurnalAddOnCode: "SUPCD",
                                jurnalAddOnValue: this.selectedSupplier.SupplierCode
                            },
                            {
                                jurnalAddOnCode: "INVGR",
                                jurnalAddOnValue: this.invoiceValue
                            }
                        ]

                    }
                    detailsJurnalTerimaBarang.push({
                        coaID: this.selectedCoa.coaID,
                        coaDescription: this.selectedCoa.coaDescription,
                        jurnalTxDebit: this.debitValue,
                        jurnalTxCredit: this.kreditValue,
                        addOns: addOns,
                        coaAccountNo: this.selectedCoa.coaAccountNo
                    })
                }

                if (this.detailsJurnalTerimaBarang.length > 0) {
                    this.closeDialogAddDetail();
                    this.selectedCoa = null;
                    this.debitValue = 0;
                    this.kreditValue = 0;
                }

                // foreach item in detailsJurnalTerimaBarang then sum debit, credit, and balance
                let totalDebit = 0, totalCredit = 0, totalBalance = 0;

                let summaryPre = {
                    debit: totalDebit,
                    credit: totalCredit,
                    balance: totalBalance
                }

                detailsJurnalTerimaBarang.forEach(item => {
                    totalDebit += parseFloat(item.jurnalTxDebit);
                    totalCredit += parseFloat(item.jurnalTxCredit);
                });
                totalBalance = totalDebit - totalCredit;
                let summary = {
                    debit: totalDebit,
                    credit: totalCredit,
                    balance: totalBalance
                }
                this.$store.commit("terimaBarang/update_detailSum", summary);
            }

        },
        deleteDetailTerimaBarang(item) {
            const index = this.detailsJurnalTerimaBarang.findIndex(
                (detail) => detail.coaID === item.coaID && detail.jurnalTxDebit === item.jurnalTxDebit && detail.jurnalTxCredit === item.jurnalTxCredit
            );
            if (index !== -1) {
                this.detailsJurnalTerimaBarang.splice(index, 1); // remove one element on index=index
                console.log("details after delete: ", this.detailsJurnalTerimaBarang)
                // recalculate summary
                let totalDebit = 0, totalCredit = 0, totalBalance = 0;
                this.detailsJurnalTerimaBarang.forEach(item => {
                    totalDebit += parseFloat(item.jurnalTxDebit);
                    totalCredit += parseFloat(item.jurnalTxCredit);
                });
                totalBalance = totalDebit - totalCredit;
                let summary = {
                    debit: totalDebit,
                    credit: totalCredit,
                    balance: totalBalance
                }
                this.$store.commit("terimaBarang/update_detailSum", summary);
            }
        },

        checkErrorFormDetail(value) {
            var errors = this.$store.state.terimaBarang.errorsAddDetail
            if (errors.includes(value)) {
                return true
            } else {
                return false
            }
        },

        checkErrorFormJurnal(value) {
            var errTambah = this.$store.state.terimaBarang.errorsAddJurnal;
            if (errTambah.includes(value)) {
                return true
            } else {
                return false
            }
        },
        formatCurrency(value) {
            if (
                value === null ||
                value === undefined ||
                value === "" ||
                value == NaN
            )
                return "";
            return (
                "Rp " +
                parseFloat(value).toLocaleString("id-ID", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })
            );
        },


        saveJurnalTerimaBarang() {
            this.$store.commit("terimaBarang/update_errorsAddJurnal", []);
            var errTambah = this.$store.state.terimaBarang.errorsAddJurnal;
            if (_.isEmpty(this.selectedJurnalType)) {
                errTambah.push("requireitemjurnaltype")
            }
            if (_.isEmpty(this.selectedPeriode)) {
                errTambah.push("requireitemperiode")
            }
            if (_.isEmpty(this.selectedDate)) {
                errTambah.push("requireitemdate")
            }
            if (_.isEmpty(this.jurnalTitle)) {
                errTambah.push("requireitemtitle")
            }
            if (_.isEmpty(this.selectedSupplier)) {
                errTambah.push("requireitemsupplier")
            }
            if (_.isEmpty(this.grniValue) && _.isEmpty(this.invoiceValue)) {
                errTambah.push("requireitemgrniinvoice")
            }
            if (_.isEmpty(this.jurnalDescription)) {
                errTambah.push("requireitemdescription")
            }

            // Validasi Balance
            if (this.detailSum.balance !== 0) {
                errTambah.push("balanceerror")
            }

            // Validasi hanya boleh ada 1 kredit
            let creditCount = 0;
            this.detailsJurnalTerimaBarang.forEach(item => {
                if (item.isCredit || item.jurnalTxCredit > 0) {
                    creditCount++;
                }
            });
            if (creditCount > 1) {
                errTambah.push("creditcounterror")
            }

            if (errTambah.length === 0) {
                this.$store.commit("terimaBarang/update_errorsAddJurnal", []);
                let prm = {
                    M_BranchCompanyID: this.user.M_BranchCompanyID,
                    M_BranchID: this.user.M_BranchID,
                    S_RegionalID: this.user.S_RegionalID,
                    periodeID: this.selectedPeriode.periodeID,
                    jurnalTitle: this.jurnalTitle,
                    jurnalTypeID: this.selectedJurnalType.JurnalTypeID,
                    jurnalDescription: this.jurnalDescription,
                    jurnalDate: this.selectedDate,
                    details: this.detailsJurnalTerimaBarang
                }
                this.$store.dispatch("terimaBarang/saveJurnal", prm) // NOTE: comment untuk debugging
                console.log("SubmitJurnal", prm)
                this.detailsJurnalTerimaBarang = [];
                this.$store.commit("terimaBarang/update_detailSum", {
                    debit: 0,
                    credit: 0,
                    balance: 0
                });
                this.$store.commit("terimaBarang/update_selectedJurnalType", {});
                this.$store.commit("terimaBarang/update_selectedPeriode", {});
                // this.$store.commit("terimaBarang/update_selectedDate", {});
                this.$store.commit("terimaBarang/update_jurnalTitle", "");
                this.$store.commit("terimaBarang/update_selectedSupplier", {});
                this.$store.commit("terimaBarang/update_grniValue", "");
                this.$store.commit("terimaBarang/update_invoiceValue", "");
                this.$store.commit("terimaBarang/update_jurnalDescription", "");
                this.closeDialog();
            }
        },
        saveEditJurnalTerimaBarang() {
            this.$store.commit("terimaBarang/update_errorsAddJurnal", []);
            var errTambah = this.$store.state.terimaBarang.errorsAddJurnal;
            if (_.isEmpty(this.selectedJurnalType)) {
                errTambah.push("requireitemjurnaltype")
            }
            if (_.isEmpty(this.selectedPeriode)) {
                errTambah.push("requireitemperiode")
            }
            if (_.isEmpty(this.selectedDate)) {
                errTambah.push("requireitemdate")
            }
            if (_.isEmpty(this.jurnalTitle)) {
                errTambah.push("requireitemtitle")
            }
            if (_.isEmpty(this.selectedSupplier)) {
                errTambah.push("requireitemsupplier")
            }
            if (_.isEmpty(this.grniValue) && _.isEmpty(this.invoiceValue)) {
                errTambah.push("requireitemgrniinvoice")
            }
            if (_.isEmpty(this.jurnalDescription)) {
                errTambah.push("requireitemdescription")
            }

            // Validasi Balance
            if (this.detailSum.balance !== 0) {
                errTambah.push("balanceerror")
            }

            // Validasi hanya boleh ada 1 kredit
            let creditCount = 0;
            this.detailsJurnalTerimaBarang.forEach(item => {
                if (item.isCredit || item.jurnalTxCredit > 0) {
                    creditCount++;
                }
            });
            if (creditCount > 1) {
                errTambah.push("creditcounterror")
            }

            if (errTambah.length === 0) {
                this.$store.commit("terimaBarang/update_errorsAddJurnal", []);
                let prm = {
                    jurnalID: this.selectedJurnal.jurnalID,
                    M_BranchCompanyID: this.selectedJurnal.M_BranchCompanyID,
                    M_BranchID: this.selectedJurnal.M_BranchID,
                    S_RegionalID: this.selectedJurnal.S_RegionalID,
                    periodeID: this.selectedPeriode.periodeID,
                    jurnalTitle: this.jurnalTitle,
                    jurnalTypeID: this.selectedJurnalType.JurnalTypeID,
                    jurnalDescription: this.jurnalDescription,
                    jurnalDate: this.selectedDate,
                    details: this.detailsJurnalTerimaBarang
                }
                this.$store.dispatch("terimaBarang/updateJurnal", prm) // NOTE: comment untuk debugging
                console.log("SubmitEditJurnal", prm)
                this.detailsJurnalTerimaBarang = [];
                this.$store.commit("terimaBarang/update_detailSum", {
                    debit: 0,
                    credit: 0,
                    balance: 0
                });
                this.$store.commit("terimaBarang/update_selectedJurnalType", {});
                this.$store.commit("terimaBarang/update_selectedPeriode", {});
                // this.$store.commit("terimaBarang/update_selectedDate", {});
                this.$store.commit("terimaBarang/update_jurnalTitle", "");
                this.$store.commit("terimaBarang/update_selectedSupplier", {});
                this.$store.commit("terimaBarang/update_grniValue", "");
                this.$store.commit("terimaBarang/update_invoiceValue", "");
                this.$store.commit("terimaBarang/update_jurnalDescription", "");
                this.closeDialog();
            }
        },
    },
    watch: {
        searchPeriode(val, old) {
            if (val == old) return;
            if (!val) return;
            if (val.length < 1) return;
            if (this.$store.state.terimaBarang.loadingAutocomplete == 1) return; // sedang ada query lain yang loading
            this.thr_search_periode(); // Tunggu 2 detik setelah input sebelum request search lagi
        },
        searchSupplier(val, old) {
            if (val == old) return;
            if (!val) return;
            if (val.length < 1) return;
            if (this.$store.state.terimaBarang.loadingAutocomplete == 1) return;
            this.thr_search_supplier();
        },
        searchCoa(val, old) {
            if (val == old) return;
            if (!val) return;
            if (val.length < 1) return;
            if (this.$store.state.terimaBarang.loadingAutocomplete == 1) return;
            this.thr_searchCoa();
        },
    },
};
</script>