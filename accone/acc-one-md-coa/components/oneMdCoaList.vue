<template>
    <v-layout>
        <!-- ERROR DIALOG -->
        <v-dialog v-model="dialog_error" max-width="500px">
            <v-card>
                <v-card-title>
                    <span>ERROR !</span>
                    <v-spacer></v-spacer>
                </v-card-title>
                <v-divider></v-divider>
                <div class="ma-3 red--text">{{ msgError }}</div>
                <v-divider></v-divider>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" flat @click="dialog_error = false">Tutup</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <!-- END ERROR DIALOG -->
        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :timeout="5000" :multi-line="false" :vertical="false" :top="true">
            {{ msgsnackbar }}
            <v-btn flat @click="updateAlert_success(false)">
                Tutup
            </v-btn>
        </v-snackbar>
        <!-- End Snackbar -->

        <v-flex xs12>
            <v-card class="mb-2" color="white">
                <v-toolbar color="blue lighten-3" dark height="50px">
                    <v-toolbar-title>CHART OF ACCOUNT MANAGEMENT</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <v-btn @click="openFormCoa(0)" icon>
                        <v-icon>add_box</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-layout row style="background:#d5e9da;padding-top:5px;" justify-left>
                    <v-flex xs6>
                        <v-list-tile>
                            <input type="text" style="width:220px" class="textinput" v-model="xsearch"
                                label="Nama Account" placeholder="Search ..." />
                            </v-list-tile-content>
                        </v-list-tile>
                    </v-flex>
                    <v-flex xs6 class="text-xs-right pr-3">
                        <v-btn @click="doPrint()" class="white--text ma-1" color="brown" dark>Print <v-icon right
                                dark>print</v-icon></v-btn>
                    </v-flex>

                </v-layout>
                <v-divider></v-divider>
                <v-layout row wrap class="scroll-container" style="max-height:600px;overflow: auto;">
                    <v-flex xs12 pl-2 pr-2 pt-2 pb-2>
                        <v-data-table :headers="headers" :items="xcoas" :loading="isLoading" hide-actions
                            class="elevation-1">
                            <template slot="items" slot-scope="props">
                                <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaAccountNo }}
                                </td>
                                <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    <span
                                        v-bind:class="{ 'font-weight-bold': (props.item.coaLevel == '1' || props.item.coaLevel == '2') && props.item.coaIsInput == 'N' }">
                                        {{ props.item.coaDescription }}
                                    </span>
                                </td>
                                <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaSubDescription }}
                                </td>
                                <td class="text-xs-center pa-2"
                                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaAccountType }}
                                </td>
                                <td class="text-xs-center pa-2"
                                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaCurrencyCode }}
                                </td>
                                <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaCashFlowCategory }}
                                </td>
                                <td class="text-xs-center pa-2"
                                    v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaIsInput }}
                                </td>
                                <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    {{ props.item.coaReportSchedule }}
                                </td>
                                <td class="text-xs-left pa-2" v-bind:class="{ 'amber lighten-4': isSelected(props.item) }"
                                    @click="selectMe(props.item)">
                                    <v-tooltip bottom>
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-icon v-bind="attrs" v-on="on" small class="ml-3"
                                                @click="editFormCoa(props.item)">edit</v-icon>
                                        </template>
                                        <span>Edit</span>
                                    </v-tooltip>
                                    <v-tooltip bottom>
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-icon v-bind="attrs" v-on="on" small class="ml-3"
                                                @click="deleteFormCoa(props.item)">delete</v-icon>
                                        </template>
                                        <span>Delete</span>
                                    </v-tooltip>
                                </td>
                            </template>
                        </v-data-table>
                    </v-flex>
                </v-layout>
                <v-divider></v-divider>
                <v-pagination style="margin-top:10px;margin-bottom:10px" color="blue lighten-3" v-model="curr_page"
                    :length="xtotal_page"></v-pagination>

                <template>
                    <v-layout row justify-center>
                        <v-dialog v-model="dialogcoa" persistent max-width="750px">
                            <v-card>
                                <v-card-title>
                                    <span class="headline">FORM CHART OF ACCOUNT MANAGEMENT</span>
                                </v-card-title>
                                <v-card-text class="pt-0 pb-0">
                                    <v-form ref="formcoa" v-model="validationcoa" lazy-validation>
                                        <v-layout wrap>
                                            <v-flex xs12>
                                                <v-text-field v-model="xaccountno" label="Account No*"
                                                    :rules="xaccountnoRules" required></v-text-field>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-text-field v-model="xdescription" label="Description*"
                                                    :rules="xdescriptionRules" required></v-text-field>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-text-field v-model="xsubdescription"
                                                    label="Sub Description"></v-text-field>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-radio-group v-model="xaccounttype" :rules="xaccounttypeRules">
                                                    <template v-slot:label>
                                                        <div>Account Type*</div>
                                                    </template>
                                                    <v-layout row pt-2>
                                                        <v-flex xs6 wrap>
                                                            <v-radio value="DB">
                                                                <template v-slot:label>
                                                                    <div>Debit</div>
                                                                </template>
                                                            </v-radio>
                                                        </v-flex>
                                                        <v-flex xs6 wrap>
                                                            <v-radio value="CR">
                                                                <template v-slot:label>
                                                                    <div>Credit</div>
                                                                </template>
                                                            </v-radio>
                                                        </v-flex>
                                                    </v-layout>
                                                </v-radio-group>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-text-field v-model="xcurrencycode"
                                                    label="Currency Code"></v-text-field>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-text-field v-model="xcashflowcategory"
                                                    label="Cash Flow Category"></v-text-field>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-checkbox v-model="isinput" label="Input"></v-checkbox>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-text-field v-model="xreportschedule"
                                                    label="Report Schedule"></v-text-field>
                                            </v-flex>
                                            <v-flex xs12>
                                                <v-text-field v-model="xlevel" label="Level"></v-text-field>
                                            </v-flex>
                                            <v-flex>
                                                <p v-for="(xerror, idx) in xerrors" class="error pl-2 pr-2"
                                                    style="color:#fff">{{ xerror.msg }}</p>
                                            </v-flex>
                                        </v-layout>
                                </v-card-text>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn color="error" flat @click="closeDialogFormCoa()">Tutup</v-btn>
                                    <v-btn v-if="xact === 'new'" color="primary" @click="saveFormCoa()">Simpan</v-btn>
                                    <v-btn v-if="xact === 'edit'" color="primary" @click="updateFormCoa()">Simpan
                                        Perubahan</v-btn>
                                </v-card-actions>
                                </v-form>
                            </v-card>
                        </v-dialog>
                    </v-layout>
                </template>

            </v-card>
        </v-flex>

        <template>

            <v-dialog v-model="dialogdeletealertaccount" max-width="30%">
                <v-card>
                    <v-card-title class="headline grey lighten-2 pt-2 pb-2" primary-title>
                        Peringatan !
                    </v-card-title>
                    <v-card-text class="pt-2 pb-2">
                        <v-layout row>
                            <v-flex xs12 d-flex>
                                <v-layout row>
                                    <v-flex pb-1 xs12>
                                        <v-layout row>
                                            <v-flex pt-2 pr-2 xs12>
                                                {{ msgalertaccount }}
                                            </v-flex>
                                        </v-layout>
                                    </v-flex>
                                </v-layout>
                            </v-flex>
                        </v-layout>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="error" flat @click="dialogdeletealertaccount = false">
                            Tutup
                        </v-btn>
                        <v-btn color="primary" @click="closeDeleteAlertAccount()">
                            Yakin lah
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

        </template>

        <one-dialog-print :title="printtitle" :width="printwidth" :height="700" :status="openprint" :urlprint="urlprint"
            @close-dialog-print="openprint = false"></one-dialog-print>
    </v-layout>
</template>

<style scoped>
.searchbox .v-input.v-text-field .v-input__slot {
    min-height: 60px;
}

.searchbox .v-btn {
    min-height: 60px;
}

table.v-table tbody td,
table.v-table tbody th {
    height: 40px;
}

table.v-table thead tr {
    height: 40px;
}

.textinput {
    -webkit-transition: width 0.4s ease-in-out;
    transition: width 0.4s ease-in-out;
    background-color: white;
    background-position: 10px 10px;
    background-repeat: no-repeat;
    padding-left: 40px;
    width: 100%;
    padding: 8px 10px;
    margin-bottom: 5px;
    box-sizing: border-box;
    border: 1px solid #607d8b;

}

.textinput:focus {
    width: 100%;
}

.textinput:focus::-webkit-input-placeholder {
    color: transparent;
}

.textinput:focus::-moz-placeholder {
    color: transparent;
}

.textinput:-moz-placeholder {
    color: transparent;
}

.boxoutline {
    color: red;
    border: 1px solid red;
    justify-content: center;
    height: 45px;
    line-height: 45px;
    padding-left: 10px;
    background: #ffffff;
    font-size: 14px;
    font-weight: 500;
    border-radius: 1px
}

.boxoutline:hover {
    background: rgba(0, 0, 0, 0.07) !important;
    font-size: 15px;
    font-weight: 700;
}

.boxsolid {
    color: #ffffff;
    border: 1px solid #ffffff;
    justify-content: center;
    height: 45px;
    line-height: 45px;
    padding-left: 10px;
    background: #f44336;
    font-size: 14px;
    font-weight: 500;
    border-radius: 1px
}

.boxsolid:hover {
    background: #f44336de;
    font-size: 15px;
    font-weight: 700;
}

.scroll-container {
    scroll-padding: 50px 0 0 50px;
}

::-webkit-scrollbar {
    width: 7px;
}

/* this targets the default scrollbar (compulsory) */

::-webkit-scrollbar-track {
    background-color: #73baf3;
}

/* the new scrollbar will have a flat appearance with the set background color */

::-webkit-scrollbar-thumb {
    background-color: #2196f3;
}

/* this will style the thumb, ignoring the track */

::-webkit-scrollbar-button {
    background-color: #0079da;
}

/* optionally, you can style the top and the bottom buttons (left and right for horizontal bars) */

::-webkit-scrollbar-corner {
    background-color: black;
}

/* if both the vertical and the horizontal bars appear, then perhaps the right bottom corner also needs to be styled */
</style>

<script>
module.exports = {
    components: {
        'one-dialog-print': httpVueLoader('../../common/oneDialogPrintX.vue')
    },
    data() {
        return {
            query: "",
            items: [],
            readonlydefault: false,
            page: 1,
            xaccountno: '',
            xdescription: '',
            xsubdescription: '',
            xaccounttype: '',
            xcurrencycode: '',
            xcashflowcategory: '',
            isinput: false,
            xreportschedule: '',
            xlevel: '',
            xaccountnoRules: [
                v => !!v || 'Account No harus diisi'
            ],
            xdescriptionRules: [
                v => !!v || 'Description harus diisi'
            ],
            xaccounttypeRules: [
                v => !!v || 'Account Type harus diisi'
            ],
            headers: [{
                text: "ACCOUNT NO",
                align: "left",
                sortable: false,
                value: "name",
                width: "15%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "DESCRIPTION",
                align: "left",
                sortable: false,
                value: "name",
                width: "20%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "SUBDESCRIPTION",
                align: "left",
                sortable: false,
                value: "name",
                width: "15%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "ACCOUNT TYPE",
                align: "left",
                sortable: false,
                value: "name",
                width: "10%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "CURRENCY CODE",
                align: "left",
                sortable: false,
                value: "name",
                width: "10%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "CASH FLOW CATEGORY",
                align: "left",
                sortable: false,
                value: "name",
                width: "10%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "INPUT",
                align: "left",
                sortable: false,
                value: "name",
                width: "5%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "REPORT SCHEDULE",
                align: "left",
                sortable: false,
                value: "name",
                width: "10%",
                class: "blue lighten-3 white--text"
            },
            {
                text: "ACTION",
                align: "left",
                sortable: false,
                value: "status",
                width: "5%",
                class: "blue lighten-3 white--text"
            }
            ],
            color: "success",
            validationcoa: false,
            xid: 0,
            msgalertaccount: "",
            dialogdeletealertaccount: false,
            formatreport: "pdf",
            urlprint: "",
            printtitle: '',
            printwidth: '100%'
        };
    },
    mounted() {
        this.$store.dispatch("coa/search")
    },
    computed: {
        xerrors() {
            return this.$store.state.coa.errors
        },
        xcoas() {
            return this.$store.state.coa.coas
        },
        isLoading() {
            return this.$store.state.coa.search_status == 1
        },
        xsearch: {
            get() {
                return this.$store.state.coa.x_search
            },
            set(val) {
                this.$store.commit("coa/update_x_search", val)
            }
        },
        curr_page: {
            get() {
                return this.$store.state.coa.current_page
            },
            set(val) {
                this.$store.commit("coa/update_current_page", val)
                this.$store.commit("coa/update_last_id", -1)
                this.$store.dispatch("coa/search")
            }
        },
        xtotal_page: {
            get() {
                return this.$store.state.coa.total_coas
            },
            set(val) {
                this.$store.commit("coa/update_total_coas", val)
            }
        },
        dialogcoa() {
            return this.$store.state.coa.dialog_form_coa
        },
        xact() {
            return this.$store.state.coa.act
        },
        snackbar: {
            get() {
                return this.$store.state.coa.alert_success
            },
            set(val) {
                this.$store.commit("coa/update_alert_success", val)
            }
        },
        msgsnackbar() {
            return this.$store.state.coa.msg_success
        },
        msgsnackbar() {
            return this.$store.state.coa.msg_success
        },
        dialog_error: {
            get() {
                return this.$store.state.coa.alert_error
            },
            set(val) {
                this.$store.commit("coa/update_alert_error", val)
            },
        },
        msgError() {
            return this.$store.state.coa.save_error_message
        },
        openprint: {
            get() {
                return this.$store.state.coa.open_print
            },
            set(val) {
                this.$store.commit("coa/update_open_print", false)
            }
        }
    },
    methods: {
        isSelected(p) {
            return p.coaID == this.$store.state.coa.selected_coa.coaID
        },
        selectMe(sc) {
            this.$store.commit("coa/update_selected_coa", sc)

        },
        closeDialogFormCoa() {
            this.$store.commit("coa/update_dialog_form_coa", false)
        },
        updateDialogStatusOrder() {
            this.$store.commit("account/update_dialog_status_order", false)
        },
        setStatusOrder(val) {
            this.$store.commit("account/update_accounts", {})
            this.$store.commit("account/update_dialog_status_order", true)
            this.$store.commit("account/update_statuss", val.statuss)
        },
        doPriceList(val) {
            console.log(location)
            var id = val.id
            location.replace("/one-ui/bank/vuex/one-md-price/" + "?id=" + id)
        },
        doPrice() {
            console.log(location)
            var id = this.xid
            location.replace("/one-ui/bank/vuex/one-md-price/" + "?id=" + id)
        },
        thr_search: _.debounce(function () {
            this.$store.dispatch("coa/search")
        }, 500),

        searchBank() {
            this.$store.dispatch("account/lookup", {
                id: this.xbank.name === "" ? "0" : this.$store.state.bank.selected_bank
                    .id,
                search: this.xsearch,
                current_page: 1,
                lastid: -1
            })
        },
        doPrint() {
            // console.log('doprint')
            this.printwidth = 1028
            this.printtitle = ""
            let user = one_user()
            tm = Date.now()
            var rptname = 'sp_rpt_acc_001'
            var formatrpt = this.formatreport

            // https://accone.aplikasi.web.id/birt/run?__report=report/one/acc/sp_rpt_acc_001.rptdesign&__format=pdf&username=admin

            this.urlprint = "/birt/run?__report=report/one/acc/" + rptname + ".rptdesign&__format=" + formatrpt + "&username=" + user.M_StaffName + "&tm=" + tm
            // console.log(this.urlprint)

            this.$store.commit("coa/update_open_print", true)
        },
        // closePrint() {
        //     this.openprint = false
        // },
        openFormCoa() {
            this.xaccountno = ''
            this.xdescription = ''
            this.xsubdescription = ''
            this.xaccounttype = ''
            this.xcurrencycode = ''
            this.xcashflowcategory = ''
            this.xreportschedule = ''
            this.xlevel = ''
            this.$refs.formcoa.reset()
            this.$refs.formcoa.resetValidation()
            this.$store.commit("coa/update_act", 'new')
            this.$store.commit("coa/update_dialog_form_coa", true)
        },
        editFormCoa(val) {
            this.xid = val.coaID
            this.xaccountno = val.coaAccountNo
            this.xdescription = val.coaDescription
            this.xsubdescription = val.coaSubDescription
            this.xaccounttype = val.coaAccountType
            this.xcurrencycode = val.coaCurrencyCode
            this.xcashflowcategory = val.coaCashFlowCategory
            this.xreportschedule = val.coaReportSchedule
            this.xlevel = val.coaLevel
            this.isinput = val.coaIsInput === 'N' ? false : true
            this.$store.commit("coa/update_act", 'edit')
            this.$store.commit("coa/update_dialog_form_coa", true)
        },
        saveFormCoa() {
            if (this.$refs.formcoa.validate()) {
                this.$store.dispatch("coa/save", {
                    accountno: this.xaccountno,
                    description: this.xdescription,
                    subdescription: this.xsubdescription,
                    accounttype: this.xaccounttype,
                    currencycode: this.xcurrencycode,
                    cashflowcategory: this.xcashflowcategory,
                    reportschedule: this.xreportschedule,
                    level: this.xlevel,
                    isinput: this.isinput === true ? "Y" : "N"
                })
            }

        },
        updateFormCoa() {
            if (this.$refs.formcoa.validate()) {

                // var prm = {
                //     accountno: this.xaccountno,
                //     description: this.xdescription,
                //     subdescription: this.xsubdescription,
                //     accounttype: this.xaccounttype,
                //     currencycode: this.xcurrencycode,
                //     cashflowcategory: this.xcashflowcategory,
                //     reportschedule: this.xreportschedule,
                //     level: this.xlevel,
                //     isinput: this.isinput === true ? "Y" : "N"
                // }

                this.$store.dispatch("coa/update", {
                    coaid: this.xid,
                    accountno: this.xaccountno,
                    description: this.xdescription,
                    subdescription: this.xsubdescription,
                    accounttype: this.xaccounttype,
                    currencycode: this.xcurrencycode,
                    cashflowcategory: this.xcashflowcategory,
                    reportschedule: this.xreportschedule,
                    level: this.xlevel,
                    isinput: this.isinput === true ? "Y" : "N"
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
        updateAlert_success(val) {
            this.$store.commit("coa/update_alert_success", val)
        },
        deleteFormCoa(data) {
            this.xid = data.coaID

            this.msgalertaccount = "Yakin, mau hapus accounting [" + data.coaAccountNo + " " + data.coaDescription + "] ?"
            this.dialogdeletealertaccount = true
        },
        closeDeleteAlertAccount() {
            this.$store.dispatch("coa/delete", {
                coaid: this.$store.state.coa.selected_coa.coaID,
                description: this.$store.state.coa.selected_coa.coaDescription
            })
            this.dialogdeletealertaccount = false
        }
    },
    watch: {
        xsearch(val, old) {
            this.xsearch = val
            this.thr_search()

        },
        search_bank(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_bank()
        },
        search_city(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_city()
        },
        search_district(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_district()
        },
        search_kelurahan(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_kelurahan()
        },
        search_company(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_company()
        },
        search_mou(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_mou()
        },
        search_doctor(val, old) {
            if (val == old) return
            if (!val) return
            if (val.length < 1) return
            if (this.$store.state.account.update_autocomplete_status == 1) return
            this.thr_search_doctor()
        }
    }
}
</script>