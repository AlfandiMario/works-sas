<template>
    <div>
        <v-dialog v-model="pengeluaranbarang_dialog" persistent width="70vw">
            <v-card>
                <v-card-title class="headline grey lighten-2" primary-title>
                    {{ opp_type == "add" ? 'FORM JURNAL PENGELUARAN BARANG': 'EDIT FORM JURNAL PENGELUARAN BARANG' }}
                </v-card-title>

                <v-card-text>
                    <v-layout wrap>
                        <v-flex xs12>
                            <v-layout>
                                <v-flex xs6 class="px-1 mb-2">
                                    <v-text-field
                                        label="COMPANY" v-model="input_company"
                                        readonly hide-details outline
                                    ></v-text-field>
                                </v-flex>
                                <v-flex class="px-1 mb-2">
                                    <v-text-field
                                        label="TIPE JURNAL" v-model="selected_jurnal_type.JurnalTypeName"
                                        readonly hide-details outline
                                    ></v-text-field>
                                    <p v-if="alert_kosong && selected_jurnal_type.JurnalTypeName == ''" class="error pl-2 pr-2" style="color:#fff">Tipe jurnal kosong</p>
                                </v-flex>
                            </v-layout>
                        </v-flex>
                        <v-flex xs12>
                            <v-layout>
                                <v-flex xs6 class="px-1 mb-2">
                                    <v-text-field
                                        label="REGIONAL" v-model="input_regional"
                                        readonly hide-details outline
                                    ></v-text-field>
                                </v-flex>
                                <v-flex class="px-1 mb-2">
                                    <v-layout>
                                        <v-flex xs6>
                                            <v-menu
                                                v-model="menufilterdate" :close-on-content-click="false" :nudge-right="40" lazy
                                                transition="scale-transition" offset-y full-width max-width="290px"
                                                min-width="290px" :disabled="!IsPeriodeSelected"
                                            >
                                                <template v-slot:activator="{ on }">
                                                    <v-text-field 
                                                        v-model="filterComputedDateFormatted" class="mr-2" label="Tanggal"
                                                        outline hide-details readonly v-on="on" :disabled="!IsPeriodeSelected" 
                                                        @blur="date = deFormatedDate(filterComputedDateFormatted)"
                                                    ></v-text-field>
                                                </template>
                                                <v-date-picker 
                                                    no-title v-model="input_date" @input="menufilterdate = false"
                                                    :min="periode_start_date" :max="periode_end_date"
                                                ></v-date-picker>
                                            </v-menu>
                                        </v-flex>
                                        <v-flex>
                                            <v-autocomplete 
                                                label="Periode" :items="list_periode" v-model="selected_periode"
                                                :search-input.sync="search_periode" hide-details
                                                auto-select-first no-filter item-text="periodeName" outline
                                                return-object :loading="search_status" no-data-text="Pilih Periode"
                                            >
                                                <template slot="item" slot-scope="{ item }">
                                                    <v-list-tile-content>
                                                        <v-list-tile-title v-text="item.periodeName"></v-list-tile-title>
                                                        <v-list-tile-sub-title v-text="item.periode"></v-list-tile-sub-title>
                                                    </v-list-tile-content>
                                                </template>
                                            </v-autocomplete>
                                            <p v-if="alert_kosong && Object.keys(selected_periode).length == 0" class="error pl-2 pr-2" style="color:#fff">Periode masih kosong</p>
                                        </v-flex>
                                    </v-layout>
                                </v-flex>
                            </v-layout>
                        </v-flex>
                        <v-flex xs12>
                            <v-layout>
                                <v-flex xs6 class="px-1 mb-2">
                                    <v-text-field
                                        label="CABANG" v-model="input_cabang"
                                        readonly hide-details outline
                                    ></v-text-field>
                                </v-flex>
                                <v-flex class="px-1 mb-2">
                                    <v-text-field
                                        label="JUDUL" v-model="input_title"
                                        hide-details outline
                                    ></v-text-field>
                                    <p v-if="alert_kosong && input_title == ''" class="error pl-2 pr-2" style="color:#fff">Title masih kosong</p>
                                </v-flex>
                            </v-layout>
                        </v-flex>
                        <v-flex>
                            <v-layout>
                                <v-textarea
                                    v-model="input_description" name="input-7-1" label="DESKRIPSI"
                                    hide-details outline
                                ></v-textarea>
                            </v-layout>
                        </v-flex>

                    </v-layout>

                    <v-layout class="pt-3 pb-1">
                        <v-flex text-xs-right>
                            <v-btn 
                                color="blue" 
                                class="white--text" @click="openDetailDialog()"
                            >TAMBAH</v-btn>
                        </v-flex>
                    </v-layout>

                    <v-layout row wrap>
                        <v-flex xs12>
                            <v-data-table
                                :headers="table_headers" :items="table_detail"
                                hide-actions class="elevation-1"
                            >
                                <template slot="items" slot-scope="props">
                                    <td class="text-xs-center pa-2">{{ props.item.coano }}</td>
                                    <td class="text-xs-center pa-2">{{ props.item.coadesc }}</td>
                                    <td class="text-xs-center pa-2">{{ formatCurrency(props.item.debet) }}</td>
                                    <td class="text-xs-center pa-2">{{ formatCurrency(props.item.kredit) }}</td>
                                    <td class="text-xs-center pa-2">
                                        <v-icon small @click="deleteDetail(props.index)">delete</v-icon>
                                    </td>
                                </template>
                                <template v-slot:footer>
                                    <tr>
                                        <td colspan="2" width="65%" class="pa-2 font-weight-bold">TOTAL</td>
                                        <td width="15%" class="text-xs-center pa-2 font-weight-bold">{{ formatCurrency(summary_detail.total_debet) }}</td>
                                        <td width="15%" class="text-xs-center pa-2 font-weight-bold">{{ formatCurrency(summary_detail.total_kredit) }}</td>
                                        <td width="5%" class="text-xs-right pa-2 font-weight-bold"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="font-weight-bold pa-2">BALANCE</td>
                                        <td width="15%" class="text-xs-right pa-2 font-weight-bold">{{ formatCurrency(summary_detail.total_balance) }}</td>
                                        <td width="5%" class="font-weight-bold text-xs-right pa-2"></td>
                                    </tr>
                                </template>
                            </v-data-table>
                        </v-flex>
                    </v-layout>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="error" flat @click="closeDialog()">TUTUP</v-btn>
                    <v-btn color="primary" @click="simpanJurnal()">SIMPAN</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <template>
            <v-dialog v-model="detail_dialog" persistent width="40vw">
                <v-card>
                    <v-card-title class="headline grey lighten-2" primary-title>FORM DETAIL</v-card-title>
                    <v-card-text>
                        <v-layout wrap>
                            <v-flex xs12 class="px-1 mb-2">
                                <v-autocomplete
                                    label="COA" :items="list_coa" v-model="selected_coa"
                                    :search-input.sync="search_coa" hide-details
                                    no-filter item-text="display" outline return-object
                                    :loading="search_status" no-data-text="Pilih Coa"
                                >
                                </v-autocomplete>
                                <p v-if="alert_kosong && Object.keys(selected_coa).length == 0" class="error pl-2 pr-2" style="color:#fff">Coa masih kosong</p>
                            </v-flex>
                            <v-flex xs12 class="px-1 mb-2">
                                <v-text-field
                                    label="DEBET" v-model="input_detail_debet"
                                    outline type="number" hide-details
                                ></v-text-field>
                            </v-flex>
                            <v-flex xs12 class="px-1 mb-2">
                                <v-text-field
                                    label="KREDIT" v-model="input_detail_kredit"
                                    outline type="number" hide-details
                                ></v-text-field>
                            </v-flex>
                        </v-layout>
                    </v-card-text>
                    <v-divider></v-divider>
                    
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="error" flat @click="closeDetailDialog()">
                            BATAL
                        </v-btn>
                        <v-btn color="primary" @click="tambahDetail()">TAMBAH</v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </template>

        <v-snackbar v-model="crud_jurnal.state" :timeout="5000" :multi-line="false" :vertical="false" :top="true" :color="crud_jurnal.color">
            {{ crud_jurnal.msg }}
            <v-btn flat @click="closeSnackbar(false)">Tutup</v-btn>
        </v-snackbar>
    </div>
</template>


<style scoped></style>

<script>
    module.exports= {
        name: "pengeluaranBarang",
        components: {
            
        },
        mounted() {
            this.$store.dispatch("pengeluaranbarang/getperiode", {search: ""})
        },
        data: () => ({
            menufilterdate: false,
            search_periode: "",
            search_coa: "",
            date: new Date().toISOString().substr(0, 10),
            table_headers: [
                {
                    text: "AKUN",
                    align: "center",
                    sortable: false,
                    value: "action",
                    width: "30%",
                    class: "pa-2 pl-2 blue lighten-3 white--text",
                },
                {
                    text: "DESKRIPSI",
                    align: "center",
                    sortable: false,
                    value: "mr",
                    width: "35%",
                    class: "pa-2 blue lighten-3 white--text",
                },
                {
                    text: "DEBET",
                    align: "center",
                    sortable: false,
                    value: "lab",
                    width: "15%",
                    class: "pa-2 blue lighten-3 white--text",
                },
                {
                    text: "KREDIT",
                    align: "center",
                    sortable: false,
                    value: "lab",
                    width: "15%",
                    class: "pa-2 blue lighten-3 white--text",
                },
                {
                    text: "AKSI",
                    align: "center",
                    sortable: false,
                    value: "lab",
                    width: "5%",
                    class: "pa-2  blue lighten-3 white--text",
                },
            ],
            alert_kosong: false,
        }),
        computed: {
            opp_type() {
                return this.$store.state.pengeluaranbarang.opp_type
            },
            user() {
                return this.$store.state.pengeluaranbarang.user;
            },
            input_company() {
                return this.$store.state.pengeluaranbarang.input_company
            },
            input_regional() {
                return this.$store.state.pengeluaranbarang.input_regional
            },
            input_cabang() {
                return this.$store.state.pengeluaranbarang.input_cabang
            },
            pengeluaranbarang_dialog: {
                get() {
                    return this.$store.state.pengeluaranbarang.keluar_barang_dialog;
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_pengeluaran_barang_dialog", val)
                }
            },
            search_status() {
                return this.$store.state.pengeluaranbarang.search_status == 1
            },
            selected_jurnal_type() {
                return this.$store.state.pengeluaranbarang.selected_jurnal_type
            },
            periode_start_date: {
                get() {
                    return this.$store.state.pengeluaranbarang.periode_start_date;
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_periode_start_date", val)
                }
            },
            periode_end_date: {
                get() {
                    return this.$store.state.pengeluaranbarang.periode_end_date;
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_periode_end_date", val)
                }
            },
            input_date: {
                get() {
                    return this.$store.state.pengeluaranbarang.input_date
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_input_date", val)
                }
            },
            input_title: {
                get() {
                    return this.$store.state.pengeluaranbarang.input_title
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_input_title", val)
                }
            },
            input_description: {
                get() {
                    return this.$store.state.pengeluaranbarang.input_description
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_input_description", val)
                }
            },
            filterComputedDateFormatted() {
                return this.formatDate(this.input_date)
            },
            IsPeriodeSelected() {
                return this.periode_start_date && this.periode_end_date
            },
            list_periode() {
                return this.$store.state.pengeluaranbarang.list_periode
            },
            selected_periode: {
                get() {
                    return this.$store.state.pengeluaranbarang.selected_periode
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_selected_periode", val)
                }
            },
            detail_dialog: {
                get() {
                    return this.$store.state.pengeluaranbarang.detail_dialog
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_detail_dialog", val)
                }
            },
            table_detail() {
                return this.$store.state.pengeluaranbarang.table_detail
            },
            summary_detail() {
                return this.$store.state.pengeluaranbarang.summary_detail
            },
            list_coa(){
                return this.$store.state.pengeluaranbarang.list_coa
            },
            selected_coa: {
                get() {
                    return this.$store.state.pengeluaranbarang.selected_coa
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_selected_coa", val)
                }
            },
            input_detail_debet: {
                get() {
                    return this.$store.state.pengeluaranbarang.input_detail_debet
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_input_detail_debet", val)
                }
            },
            input_detail_kredit: {
                get() {
                    return this.$store.state.pengeluaranbarang.input_detail_kredit
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_input_detail_kredit", val)
                }
            },
            crud_jurnal_status: {
                get() {
                    return this.$store.state.pengeluaranbarang.crud_jurnal_status
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_crud_jurnal_status", val)
                }
            },
            crud_jurnal: {
                get() {
                    return this.$store.state.pengeluaranbarang.crud_jurnal
                },
                set(val) {
                    this.$store.commit("pengeluaranbarang/update_crud_jurnal", val)
                }
            }
        },
        methods: {
            closeDialog() {
                this.alert_kosong = false
                this.$store.commit("pengeluaranbarang/update_selected_jurnal_type", {})
                this.$store.commit("pengeluaranbarang/update_selected_periode", {})
                this.$store.commit("pengeluaranbarang/update_input_title", "")
                this.$store.commit("pengeluaranbarang/update_input_description", "")
                this.selected_coa = {}
                this.input_detail_debet = 0
                this.input_detail_kredit = 0
                this.$store.commit("pengeluaranbarang/update_pengeluaran_barang_dialog", false)
            },
            openDetailDialog() {
                let tipejurnal = this.selected_jurnal_type
                let periode = this.selected_periode
                let title = this.input_title
                if (Object.keys(tipejurnal).length == 0 || Object.keys(periode).length == 0 || title == "") {
                    this.alert_kosong = true
                    return
                }

                this.selected_coa = {}
                this.input_detail_debet = 0
                this.input_detail_kredit = 0
                this.detail_dialog = true
                this.alert_kosong = false
            },
            closeDetailDialog() {
                this.selected_coa = {}
                this.input_detail_debet = 0
                this.input_detail_kredit = 0
                this.detail_dialog = false
            },
            closeSnackbar(state) {
                let a = {
                    state: state,
                    msg: "",
                    color: ""
                }
                this.crud_jurnal = a
            },
            simpanJurnal() {
                if (this.summary_detail.total_kredit != this.summary_detail.total_debet) {
                    let a = {
                        state: true,
                        msg: "Balance kredit dan debet tidak sama",
                        color: "warning"
                    }
                    this.crud_jurnal = a
                    return
                }

                let obj = {
                    periodeid: this.selected_periode.periodeID,
                    title: this.input_title,
                    description: this.input_description,
                    date: this.input_date,
                    jurnaltypeid: this.selected_jurnal_type.JurnalTypeID,
                    detail: this.table_detail
                }

                let operation = this.$store.state.pengeluaranbarang.opp_type
                if (operation == "add") {
                    this.$store.dispatch("pengeluaranbarang/simpan_jurnal", obj)   
                } else if (operation == "edit") {
                    obj.jurnalid = this.$store.state.pengeluaranbarang.current_jurnal.jurnalID
                    this.$store.dispatch("pengeluaranbarang/simpan_edit_jurnal", obj)
                }
            },
            tambahDetail() {
                if (Object.keys(this.selected_coa).length == 0) {
                    this.alert_kosong = true
                    return
                }

                let obj = {
                    coaid: this.selected_coa.id,
                    coano: this.selected_coa.number,
                    coadesc: this.selected_coa.keterangan,
                    debet: this.input_detail_debet,
                    kredit: this.input_detail_kredit
                }

                this.$store.dispatch("pengeluaranbarang/add_detail_table", obj)
                this.alert_kosong = false
                this.detail_dialog = false
            },
            deleteDetail(index) {
                this.$store.dispatch("pengeluaranbarang/del_detail_table", index)
            },
            formatDate(date) {
                if (!date) return null

                const [year, month, day] = date.split('-')
                return `${day}-${month}-${year}`
            },
            deFormatedDate(date) {
                if (!date) return null

                const [day, month, year] = date.split('-')
                return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
            },
            fn_search_periode: _.debounce(function () {
                this.$store.dispatch("pengeluaranbarang/getperiode", {search: this.search_periode})
            }, 1000),
            fn_search_coa: _.debounce(function() {
                this.$store.dispatch("pengeluaranbarang/getcoa", {search: this.search_coa})
            }, 1000),
            formatCurrency(val) {
                let price = parseFloat(val);
                let IDR = new Intl.NumberFormat("id-ID", {
                    style: "currency",
                    currency: "IDR",
                });
                return `${IDR.format(price)}`;
            },
        },
        watch: {
            search_periode(val, old) {
                if (val == old) return;
                if (!val) return;
                if (val.length < 1) return;
                if (this.$store.state.pengeluaranbarang.search_status == 1) return;
                this.fn_search_periode();
            },
            search_coa(val, old) {
                if (val == old) return;
                if (!val) return;
                if (val.length < 1) return;
                if (this.$store.state.pengeluaranbarang.search_status == 1) return;
                this.fn_search_coa();
            }
        },
    };
</script>