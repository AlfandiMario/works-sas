<template>
<div>
    <v-dialog v-model="dialog" persistent width="70vw">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          FORM JURNAL UMUM
        </v-card-title>
  
        <v-card-text>
          <v-layout wrap>
            <v-flex xs12>
                <v-layout>
                    <v-flex xs6 class="px-1 mb-2">
                        <v-text-field
                            label="COMPANY"
                            v-model="user.M_BranchCompanyName"
                            readonly
                            hide-details
                            outline
                        ></v-text-field>
                    </v-flex>
                    <v-flex xs6 class="px-1 mb-2">
                        <v-text-field
                            label="JURNAL TYPE"
                            v-model="selected_jurnaltype.JurnalTypeName"
                            readonly
                            hide-details
                            outline
                        ></v-text-field>
                        <p v-if="checkErrorFormJurnal('requireitemjurnaltype')" class="error pl-2 pr-2" style="color:#fff">Jurnal type harus dipilih</p>
                    </v-flex>
                </v-layout>
            </v-flex>
            <v-flex xs12>
                <v-layout>
                    <v-flex xs6 class="px-1 mb-2">
                        <v-text-field
                            label="REGIONAL"
                            v-model="user.S_RegionalName"
                            readonly
                            hide-details
                            outline
                        ></v-text-field>
                    </v-flex>
                    <v-flex xs6 class="px-1 mb-2">
                        <v-layout>
                            <v-flex xs6>
                                <v-menu v-model="menufilterdate" :close-on-content-click="false" :nudge-right="40" lazy
                                    transition="scale-transition" offset-y full-width max-width="290px" min-width="290px"
                                    :disabled="!isPeriodeSelected">
                                    <template v-slot:activator="{ on }">
                                        <v-text-field v-model="filterComputedDateFormatted" class="mr-2" label="Tanggal"
                                            outline hide-details readonly v-on="on"
                                            :disabled="!isPeriodeSelected"
                                            @blur="date = deFormatedDate(filterComputedDateFormatted)"></v-text-field>
                                    </template>
                                    <v-date-picker no-title v-model="xdate"
                                        :min="periodeStartDate"
                                        :max="periodeEndDate"
                                        @input="menufilterdate = false"></v-date-picker>
                                </v-menu>
                            </v-flex>
                            <v-flex xs6>
                                <v-autocomplete label="Periode" :items="xperiode" v-model="selected_periode"
                                        :search-input.sync="search_item_periode" hide-details
                                        auto-select-first no-filter item-text="periodeName" outline return-object
                                        :disabled="this.$store.state.jurnalumum.is_posted === 'Y'"
                                        :loading="isLoading" no-data-text="Pilih Peridoe">
                                        <template slot="item" slot-scope="{ item }">
                                            <v-list-tile-content>
                                                <v-list-tile-title v-text="item.periodeName"></v-list-tile-title>
                                                <v-list-tile-sub-title
                                                    v-text="item.periode"
                                                ></v-list-tile-sub-title>
                                            </v-list-tile-content>
                                        </template>
                                </v-autocomplete>
                                <p v-if="checkErrorFormJurnal('requireitemperiode')" class="error pl-2 pr-2" style="color:#fff">Periode harus dipilih</p>
                            </v-flex>
                        </v-layout>
                    </v-flex>
                </v-layout>
            </v-flex>
            <v-flex xs12>
                <v-layout>
                    <v-flex xs6 class="px-1 mb-2">
                        <v-text-field
                            label="BRANCH"
                            v-model="user.M_BranchName"
                            readonly
                            hide-details
                            outline
                        ></v-text-field>
                    </v-flex>
                    <v-flex xs6 class="px-1 mb-2">
                        <v-text-field
                            label="JUDUL"
                            v-model="xtitle"
                            hide-details
                            outline
                            :disabled="this.$store.state.jurnalumum.is_posted === 'Y'"
                        ></v-text-field>
                        <p v-if="checkErrorFormJurnal('requireitemtitle')" class="error pl-2 pr-2" style="color:#fff">Judul harus diisi</p>
                    </v-flex>
                </v-layout>
            </v-flex>
            <v-flex>
                <v-layout>
                    <v-textarea
                        v-model="xdeskripsi"
                        name="input-7-1"
                        label="DESKRIPSI"
                        hide-detials
                        outline
                        :disabled="this.$store.state.jurnalumum.is_posted === 'Y'"
                    ></v-textarea>
                </v-layout>
            </v-flex>
          </v-layout>

          <v-layout class="pt-3 pb-1">
            <v-flex text-xs-right>
                <v-btn :disabled="this.$store.state.jurnalumum.is_posted === 'Y'" color="blue"  class="white--text" @click="opendialogdetail()">TAMBAH</v-btn>
            </v-flex>
          </v-layout>


            <v-layout row wrap>
                <v-flex xs12>
                    <v-data-table
                        :headers="headers"
                        :items="jurnaldetails"
                        hide-actions
                        class="elevation-1"
                    >
                        <template slot="items" slot-scope="props">
                        <td
                            class="text-xs-center pa-2"
                        >
                            {{ props.item.account }}
                        </td>
                        <td
                            class="text-xs-left pa-2"
                        >
                            {{ props.item.description }}
                        </td>
                        <td
                            class="text-xs-right pa-2"
                        >
                            {{ formatCurrency(props.item.debit) }}
                        </td>
                        <td
                        class="text-xs-right pa-2"
                        >
                            {{ formatCurrency(props.item.credit) }}
                        </td>
                        <td
                            class="text-xs-center pa-2"
                        >
                            <v-icon small @click="deleteDetailJurnal(props.item)"
                            >delete</v-icon
                            >
                        </td>
                        </template>

                        <template v-slot:footer>
                        <tr>
                            <td colspan="2" class="font-weight-bold pa-2">TOTAL</td>

                            <td class="text-xs-right pa-2 font-weight-bold">
                                {{ formatCurrency(xsummary.debit) }}
                            </td>
                            <td class="text-xs-right pa-2 font-weight-bold">
                                {{ formatCurrency(xsummary.credit) }}
                            </td>
                            <td width="5%" class="font-weight-bold text-xs-right pa-2"></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="font-weight-bold pa-2">BALANCE</td>
                            <td width="15%" class="text-xs-right pa-2 font-weight-bold">
                                {{ formatCurrency(xsummary.balance) }}
                            </td>
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
          <v-btn color="error" flat @click="closeDialog()">
            TUTUP
          </v-btn>
          <v-btn v-if="xact === 'add'" color="primary" @click="saveJurnalUmum()">SIMPAN</v-btn>
          <v-btn v-if="xact === 'edit'" :disabled="this.$store.state.jurnalumum.is_posted === 'Y'" color="primary" @click="saveEditJurnalUmum()">SIMPAN PERUBAHAN</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <template>
        <v-dialog v-model="dialogdetail" persistent width="40vw">
            <v-card>
                <v-card-title class="headline grey lighten-2" primary-title>
                    FORM DETAIL
                </v-card-title>

                <v-card-text>
                    <v-layout wrap>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-autocomplete label="COA" :items="xcoalist" v-model="selected_coa"
                                    :search-input.sync="search_item_coa" hide-details
                                    auto-select-first no-filter item-text="coaDescription" outline return-object
                                    :loading="isLoading" no-data-text="Pilih Coa" clearable>
                                    <template slot="item" slot-scope="{ item }">
                                        <v-list-tile-content>
                                            <v-list-tile-title v-text="item.coaDescription"></v-list-tile-title>
                                            <v-list-tile-sub-title v-text="item.coaAccountNo"></v-list-tile-sub-title>
                                        </v-list-tile-content>
                                    </template>
                            </v-autocomplete>
                            <p v-if="checkErrorFormDetail('requireitemcoa')" class="error pl-2 pr-2" style="color:#fff">Coa harus dipilih</p>
                        </v-flex>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-text-field
                                label="DEBET"
                                v-model="xdebet"
                                hide-details
                                outline
                                type="number"
                                @focus="clearXdebet"
                                @blur="resetXdebet"
                            ></v-text-field>
                        </v-flex>
                        <v-flex xs12 class="px-1 mb-2">
                            <v-text-field
                            label="KREDIT"
                            v-model="xkredit"
                            hide-details
                            outline
                            type="number"
                            @focus="clearXkredit"
                            @blur="resetXkredit"
                            ></v-text-field>
                        </v-flex>
                    </v-layout>
                </v-card-text>

                <v-divider></v-divider>
        
                <v-card-actions>
                <v-spacer></v-spacer>
                    <v-btn color="error" flat @click="closeDialogDetail()">
                        BATAL
                    </v-btn>
                    <v-btn color="primary" @click="addDetailJurnal()">TAMBAHKAN</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </template>

    <template>
        <one-dialog-detail-info :status="opendialogdetailinfo" :msg="msgdetailinfo" @close-dialog-info="opendialogdetailinfo = false"></one-dialog-detail-info>
    </template>
</div>
</template>
  
<style scoped>

</style>
  
  <script>
    module.exports = {
      name: "JurnalUmum",
      components: {
        'one-dialog-detail-info':httpVueLoader('../../common/oneDialogInfo.vue'),
      },
      mounted() {
        this.$store.dispatch("jurnalumum/getjurnaltype")
        this.$store.dispatch("jurnalumum/getperiode", this.search_item_periode)
        this.$store.dispatch("jurnalumum/searchcoa", this.search_item_coa)
      },
      data: () => ({
        menufilterdate: false,
        date: new Date().toISOString().substr(0, 10),
        search_item_periode: "",
        search_item_coa: "",
        xdebet: 0,
        xkredit: 0,
        headers: [
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
      }),
      computed: {
        isPeriodeSelected() {
            return this.$store.state.jurnalumum.periodeStartDate && this.$store.state.jurnalumum.periodeEndDate;
        },
        user() {
          return this.$store.state.jurnalumum.user;
        },
        dialog: {
          get() {
            return this.$store.state.jurnalumum.dialog_is_active
          },
          set(val) {
            this.$store.commit("jurnalumum/update_dialog_is_active", val);
          },
        },
        xjurnaltype() {
            return this.$store.state.jurnalumum.jurnaltypes
        },
        selected_jurnaltype() {
            if (this.xact == "edit") {
                return this.$store.state.jurnalumum.selected_jurnaltype
            } else {
                return this.$store.state.jurnalumum.x_drop_tipe_jurnal;
            }
        },
        // selected_jurnaltype: {
        //   get() {
        //     return this.$store.state.jurnalumum.selected_jurnaltype
        //   },
        //   set(val) {
        //     this.$store.commit("jurnalumum/update_selected_jurnaltype", val);
        //   },
        // },
        xdate: {
            get() {
                return this.$store.state.jurnalumum.xdate
            },
            set(val) {
                this.$store.commit("jurnalumum/update_xdate", val)
            }
        },
        filterComputedDateFormatted() {
            return this.formatDate(this.xdate)
        },
        xperiode() {
            return this.$store.state.jurnalumum.periodes
        },
        selected_periode: {
          get() {
            return this.$store.state.jurnalumum.selected_periode
          },
          set(val) {
            this.$store.commit("jurnalumum/update_selected_periode", val);
            this.$store.commit("jurnalumum/update_periodeStartDate", val.periodeStartDate);
            this.$store.commit("jurnalumum/update_periodeEndDate", val.periodeEndDate);
          },
        },
        isLoading() {
            return this.$store.state.jurnalumum.autocomplete_status == 1
        },
        dialogdetail: {
            get() {
                return this.$store.state.jurnalumum.dialogdetail
            },
            set(val) {
                this.$store.commit("jurnalumum/update_dialogdetail",val)
            }
        },
        xcoalist() {
            return this.$store.state.jurnalumum.coalist
        },
        selected_coa: {
            get() {
                return this.$store.state.jurnalumum.selected_coa
            },
            set(val) {
                this.$store.commit("jurnalumum/update_selected_coa",val)
            }
        },
        xsummary() {
            return this.$store.state.jurnalumum.xsummary
        },
        opendialogdetailinfo: {
            get() {
                return this.$store.state.jurnalumum.open_dialog_detail_info
            },
            set(val) {
                this.$store.commit("jurnalumum/update_open_dialog_detail_info",val)
            }
        },
        msgdetailinfo: {
            get() {
                return this.$store.state.jurnalumum.msg_detail_info
            },
            set(val) {
                this.$store.commit("jurnalumum/update_msg_detail_info",val)
            }
        },
        xact: {
            get() {
                return this.$store.state.jurnalumum.act
            },
            set(val) {
                this.$store.commit("jurnalumum/update_act", val)
            }
        },
        xtitle: {
            get() {
                return this.$store.state.jurnalumum.title
            },
            set(val) {
                this.$store.commit("jurnalumum/update_title", val)
            }
        },
        xdeskripsi: {
            get() {
                return this.$store.state.jurnalumum.deskripsi
            },
            set(val) {
                this.$store.commit("jurnalumum/update_deskripsi", val)
            }
        },
        jurnaldetails() {
            return this.$store.state.jurnalumum.jurnaldetails
        },
        selected_jurnaldetail: {
            get() {
                return this.$store.state.jurnalumum.selected_jurnaldetail
            },
            set(val) {
                this.$store.commit("jurnalumum/update_selected_jurnaldetail", val)
            }
        },
        periodeStartDate: {
            get() {
                return this.$store.state.jurnalumum.periodeStartDate
            },
            set(val) {
                this.$store.commit("jurnalumum/update_periodeStartDate", val)
            }
        },
        periodeEndDate: {
            get() {
                return this.$store.state.jurnalumum.periodeEndDate
            },
            set(val) {
                this.$store.commit("jurnalumum/update_periodeEndDate", val)
            }
        },
      },
      methods: {
        getCurrentYearStartDate() {
            const currentYear = new Date().getFullYear();
            return new Date(currentYear, 0, 1); // 01 Januari tahun berjalan
        },
        clearXkredit() {
            if (this.xkredit === 0) {
                this.xkredit = '';
            }
        },
        resetXkredit() {
            if (this.xkredit === '' || this.xkredit === null) {
                this.xkredit = 0;
            }
        },
        clearXdebet() {
            if (this.xdebet === 0) {
                this.xdebet = '';
            }
        },
        resetXdebet() {
            if (this.xdebet === '' || this.xdebet === null) {
                this.xdebet = 0;
            }
        },
        selectMe(sc) {
            this.selected_jurnaldetail = sc
        },
        closeDialog() {
            if (this.xact === "add") {
                this.$store.commit("jurnalumum/update_dialog_is_active", false);
                this.$store.commit("jurnalumum/update_selected_jurnaltype", this.$store.state.jurnalumum.selected_jurnaltype_u);
                this.$store.commit("jurnalumum/update_jurnaldetails", [])
                this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
                this.$store.commit("jurnalumum/update_selected_periode",{})
                this.$store.commit("jurnalumum/update_title","")
                this.$store.commit("jurnalumum/update_deskripsi","")
                this.$store.commit("jurnalumum/update_xdate",moment(new Date()).format('YYYY-MM-DD'))
                this.$store.commit("jurnalumum/update_xsummary",{"debit": 0, "credit": 0, "balance": 0})
                this.$store.commit("jurnalumum/update_periodeStartDate", null);
                this.$store.commit("jurnalumum/update_periodeEndDate", null);
                this.$store.commit("jurnalumum/update_x_drop_tipe_jurnal", {})
            } else if (this.xact === "edit") {
                this.$store.commit("jurnalumum/update_dialog_is_active", false);
                this.$store.commit("jurnalumum/update_selected_jurnaltype", this.$store.state.jurnalumum.selected_jurnaltype_u);
                this.$store.commit("jurnalumum/update_jurnaldetails", [])
                this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
                this.$store.commit("jurnalumum/update_selected_periode",{})
                this.$store.commit("jurnalumum/update_title","")
                this.$store.commit("jurnalumum/update_deskripsi","")
                this.$store.commit("jurnalumum/update_xdate",moment(new Date()).format('YYYY-MM-DD'))
                this.$store.commit("jurnalumum/update_xsummary",{"debit": 0, "credit": 0, "balance": 0})
                this.$store.commit("jurnalumum/update_periodeStartDate", this.$store.state.jurnalumum.selected_periode.periodeStartDate);
                this.$store.commit("jurnalumum/update_periodeEndDate", this.$store.state.jurnalumum.selected_periode.periodeEndDate);
                this.$store.dispatch("jurnalumum/search", {
                    regionalid: this.$store.state.jurnalumum.user.S_RegionalID,
                    branchid: this.$store.state.jurnalumum.user.M_BranchID,
                    current_page: this.$store.state.jurnalumum.current_page,
                    search: this.$store.state.jurnalumum.x_search,
                    startdate: this.$store.state.jurnalumum.start_date,
                    enddate: this.$store.state.jurnalumum.end_date,
                    last_id: -1
                })
            }
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
        thr_search_periode: _.debounce(function () {
            this.$store.dispatch("jurnalumum/getperiode", this.search_item_periode)
        }, 2000),
        thr_search_coa: _.debounce(function () {
            this.$store.dispatch("jurnalumum/searchcoa", this.search_item_coa)
        }, 2000),
        opendialogdetail() {
            this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
            var errorssj = this.$store.state.jurnalumum.errors_save_jurnal

            if(_.isEmpty(this.$store.state.jurnalumum.selected_jurnaltype)) {
                errorssj.push("requireitemjurnaltype")
            }
            if(_.isEmpty(this.$store.state.jurnalumum.selected_periode)) {
                errorssj.push("requireitemperiode")
            }
            if(_.isEmpty(this.$store.state.jurnalumum.title)) {
                errorssj.push("requireitemtitle")
            }
            // cek error
            if (errorssj.length === 0) {
                // cek rentang periode
                if (this.$store.state.jurnalumum.periodeStartDate && this.$store.state.jurnalumum.periodeEndDate) {
                    let startDate = new Date(this.$store.state.jurnalumum.periodeStartDate);
                    let endDate = new Date(this.$store.state.jurnalumum.periodeEndDate);
                    let selectedDate = new Date(this.xdate);

                    if (selectedDate < startDate || selectedDate > endDate) {
                        let msg = "Tanggal tidak sesuai rentang periode, silahkan pilih tanggal sesuai rentang periode";
                        this.$store.commit("jurnalumum/update_msg_detail_info", msg);
                        this.$store.commit("jurnalumum/update_open_dialog_detail_info", true);
                    } else {
                        this.$store.commit("jurnalumum/update_dialogdetail",true)
                    }
                }
            }
        },
        closeDialogDetail() {
            this.$store.commit("jurnalumum/update_dialogdetail",false)
            this.$store.commit("jurnalumum/update_errors",[])
            this.$store.commit("jurnalumum/update_selected_coa",{})
            this.xkredit = 0
            this.xdebet = 0
        },
        checkErrorFormDetail(value) {
            var errors = this.$store.state.jurnalumum.errors
            if (errors.includes(value)) {
                return true
            } else {
                return false
            }
        },
        checkErrorFormJurnal(value) {
            var errorssj = this.$store.state.jurnalumum.errors_save_jurnal
            if (errorssj.includes(value)) {
                return true
            } else {
                return false
            }
        },
        addDetailJurnal() {
            this.$store.commit("jurnalumum/update_errors",[])
            var errors = this.$store.state.jurnalumum.errors
            if(_.isEmpty(this.selected_coa)) {
                errors.push("requireitemcoa")
            }

            if (errors.length === 0) {
                this.$store.commit("jurnalumum/update_errors",[])
                let jurnaldetails = this.jurnaldetails
                jurnaldetails.push({
                    coaid: this.selected_coa.coaID,
                    account: this.selected_coa.coaAccountNo,
                    description: this.selected_coa.coaDescription,
                    debit: this.xdebet,
                    credit: this.xkredit
                })

                if (this.jurnaldetails.length > 0) {
                    this.$store.commit("jurnalumum/update_dialogdetail",false)
                    this.selected_coa = ""
                    this.xdebet = 0
                    this.xkredit = 0
                }

                let totalDebit = 0
                let totalKredit = 0
                for (let item = 0; item < this.jurnaldetails.length; item++) {
                    const element = this.jurnaldetails[item];
                    totalDebit += parseFloat(element.debit)
                    totalKredit += parseFloat(element.credit)
                }

                let totalBalance = totalDebit - totalKredit;
                let summary = {
                    "debit": totalDebit,
                    "credit": totalKredit,
                    "balance": totalBalance
                }
                this.$store.commit("jurnalumum/update_xsummary",summary)
            }
        },
        formatCurrency(val) {
          // Format the price above to USD using the locale, style, and currency.
          let price = parseFloat(val);
          let USDollar = new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
          });
  
          // console.log(
          //   `The formated version of ${price} is ${USDollar.format(price)}`
          // );
          return `${USDollar.format(price)}`;
        },
        deleteDetailJurnal(item) {
            let indexid = this.$store.state.jurnalumum.jurnaldetails.findIndex(
                (detail) => detail.coaid === item.coaid
            );

            if (indexid !== -1) {
                let arrjurnaldetails = this.$store.state.jurnalumum.jurnaldetails
                arrjurnaldetails.splice(indexid, 1)
                this.$store.state.jurnalumum.jurnaldetails = arrjurnaldetails

                let totalDebit = 0
                let totalKredit = 0
                for (let item = 0; item < this.$store.state.jurnalumum.jurnaldetails.length; item++) {
                    const element = this.$store.state.jurnalumum.jurnaldetails[item];
                    totalDebit += parseFloat(element.debit)
                    totalKredit += parseFloat(element.credit)
                }

                let totalBalance = totalDebit - totalKredit;
                let summary = {
                    "debit": totalDebit,
                    "credit": totalKredit,
                    "balance": totalBalance
                }
                this.$store.commit("jurnalumum/update_xsummary",summary)
            }

        },
        saveJurnalUmum() {
            this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
            var errorssj = this.$store.state.jurnalumum.errors_save_jurnal

            if(_.isEmpty(this.$store.state.jurnalumum.selected_jurnaltype)) {
                errorssj.push("requireitemjurnaltype")
            }
            if(_.isEmpty(this.$store.state.jurnalumum.selected_periode)) {
                errorssj.push("requireitemperiode")
            }
            if(_.isEmpty(this.$store.state.jurnalumum.title)) {
                errorssj.push("requireitemtitle")
            }

            // cek error
            if (errorssj.length === 0) {
                this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
                // cek debit dan credit harus balance
                if (this.$store.state.jurnalumum.xsummary.debit > this.$store.state.jurnalumum.xsummary.credit || this.$store.state.jurnalumum.xsummary.credit > this.$store.state.jurnalumum.xsummary.debit) {
                    var msg = "Total debit dan kredit harus balance"
                    this.$store.commit("jurnalumum/update_msg_detail_info",msg)
                    this.$store.commit("jurnalumum/update_open_dialog_detail_info", true)
                } else {
                    // cek rentang periode
                    if (this.$store.state.jurnalumum.periodeStartDate && this.$store.state.jurnalumum.periodeEndDate) {
                        let startDate = new Date(this.$store.state.jurnalumum.periodeStartDate);
                        let endDate = new Date(this.$store.state.jurnalumum.periodeEndDate);
                        let selectedDate = new Date(this.xdate);

                        if (selectedDate < startDate || selectedDate > endDate) {
                            let msg = "Tanggal tidak sesuai rentang periode, silahkan pilih tanggal sesuai rentang periode";
                            this.$store.commit("jurnalumum/update_msg_detail_info", msg);
                            this.$store.commit("jurnalumum/update_open_dialog_detail_info", true);
                        } else {
                            let prm = {
                                branchcompanyid: this.$store.state.jurnalumum.user.M_BranchCompanyID,
                                regionalid: this.$store.state.jurnalumum.user.S_RegionalID,
                                branchid: this.$store.state.jurnalumum.user.M_BranchID,
                                periodeid: this.$store.state.jurnalumum.selected_periode.periodeID,
                                title: this.$store.state.jurnalumum.title,
                                description: this.$store.state.jurnalumum.deskripsi,
                                date: this.$store.state.jurnalumum.xdate,
                                typeid: this.$store.state.jurnalumum.selected_jurnaltype.JurnalTypeID,
                                detailjurnal: this.$store.state.jurnalumum.jurnaldetails,
                                current_page: this.$store.state.jurnalumum.current_page,
                                search: this.$store.state.jurnalumum.x_search,
                                startdate: this.$store.state.jurnalumum.start_date,
                                enddate: this.$store.state.jurnalumum.end_date,
                                last_id: -1
                            }

                            this.$store.dispatch("jurnalumum/savejurnalumum",prm)
                            this.$store.commit("jurnalumum/update_dialog_is_active", false);
                            this.$store.commit("jurnalumum/update_selected_jurnaltype", this.$store.state.jurnalumum.selected_jurnaltype_u);
                            this.$store.commit("jurnalumum/update_jurnaldetails", [])
                            this.$store.commit("jurnalumum/update_selected_periode",{})
                            this.$store.commit("jurnalumum/update_title","")
                            this.$store.commit("jurnalumum/update_deskripsi","")
                            this.$store.commit("jurnalumum/update_xdate",moment(new Date()).format('YYYY-MM-DD'))
                            this.$store.commit("jurnalumum/update_xsummary",{"debit": 0, "credit": 0, "balance": 0})
                        }
                    }
                }
            }
        },
        saveEditJurnalUmum() {
            this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
            var errorssj = this.$store.state.jurnalumum.errors_save_jurnal

            if(_.isEmpty(this.$store.state.jurnalumum.selected_jurnaltype)) {
                errorssj.push("requireitemjurnaltype")
            }
            if(_.isEmpty(this.$store.state.jurnalumum.selected_periode)) {
                errorssj.push("requireitemperiode")
            }
            if(_.isEmpty(this.$store.state.jurnalumum.title)) {
                errorssj.push("requireitemtitle")
            }

            if (errorssj.length === 0) {
                this.$store.commit("jurnalumum/update_errors_save_jurnal",[])
                if (this.$store.state.jurnalumum.xsummary.debit > this.$store.state.jurnalumum.xsummary.credit || this.$store.state.jurnalumum.xsummary.credit > this.$store.state.jurnalumum.xsummary.debit) {
                    var msg = "Total debit dan kredit harus balance"
                    this.$store.commit("jurnalumum/update_msg_detail_info",msg)
                    this.$store.commit("jurnalumum/update_open_dialog_detail_info", true)
                } else {
                    let prm = {
                        id: this.$store.state.jurnalumum.selected_jurnalumum.id,
                        branchcompanyid: this.$store.state.jurnalumum.user.M_BranchCompanyID,
                        regionalid: this.$store.state.jurnalumum.user.S_RegionalID,
                        branchid: this.$store.state.jurnalumum.user.M_BranchID,
                        periodeid: this.$store.state.jurnalumum.selected_periode.periodeID,
                        title: this.$store.state.jurnalumum.title,
                        description: this.$store.state.jurnalumum.deskripsi,
                        date: this.$store.state.jurnalumum.xdate,
                        typeid: this.$store.state.jurnalumum.selected_jurnaltype.JurnalTypeID,
                        detailjurnal: this.$store.state.jurnalumum.jurnaldetails,
                        current_page: this.$store.state.jurnalumum.current_page,
                        search: this.$store.state.jurnalumum.x_search,
                        startdate: this.$store.state.jurnalumum.start_date,
                        enddate: this.$store.state.jurnalumum.end_date,
                        last_id: -1
                    }

                    this.$store.dispatch("jurnalumum/editjurnalumum",prm)
                    this.$store.commit("jurnalumum/update_dialog_is_active", false);
                    this.$store.commit("jurnalumum/update_selected_jurnaltype", this.$store.state.jurnalumum.selected_jurnaltype_u);
                    this.$store.commit("jurnalumum/update_jurnaldetails", [])
                    this.$store.commit("jurnalumum/update_selected_periode",{})
                    this.$store.commit("jurnalumum/update_title","")
                    this.$store.commit("jurnalumum/update_deskripsi","")
                    this.$store.commit("jurnalumum/update_xsummary",{"debit": 0, "credit": 0, "balance": 0})
                }
            }
        }
      },
      watch: {
        search_item_periode(val, old) {
            if (val == old) return;
            if (!val) return;
            if (val.length < 1) return;
            if (this.$store.state.jurnalumum.update_autocomplete_status == 1) return;
            this.thr_search_periode();
         },
         search_item_coa(val, old) {
            if (val == old) return;
            if (!val) return;
            if (val.length < 1) return;
            if (this.$store.state.jurnalumum.update_autocomplete_status == 1) return;
            this.thr_search_coa();
         },
      },
    };
  </script>
  