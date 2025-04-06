<template>
        <v-layout column pb-2>
            <v-card class="mb-2">
                <v-layout row pa-2  align-center wrap >
                    <table>
                        <tr>
                            <th class="text-md-center pt-2 pb-2"> NOTA </th>
                            <th class="text-md-center pt-2 pb-2"> TIPE PEMBAYARAN </th>
                            <th class="text-md-center pt-2 pb-2">JUMLAH</th>
                            <th class="text-md-center pt-2 pb-2">USER</th>
                            <th class="text-md-center pt-2 pb-2">AKSI</th>
                        </tr>
                        <tr class="mini-input" v-if="notes.length > 0" v-for="(note,index) in notes">
                            <td width="30%" v-bind:class="{ 'red--text':note.note_active === 'N','primary--text':note.note_amount < 0}"  style="text-align:center;vertical-align:center;" align="center" >{{note.note_number}}</td>
                            <td width="30%" v-bind:class="{ 'red--text':note.note_active === 'N','primary--text':note.note_amount < 0}" class="text-md-center pl-3 pr-3">{{note.paymenttypes_name}}</td>
                            <td width="15%" v-bind:class="{ 'red--text':note.note_active === 'N','primary--text':note.note_amount < 0}" class="text-md-right pl-3 pr-3">{{convertMoney(note.note_amount)}}</td>
                            <td width="20%" v-bind:class="{ 'red--text':note.note_active === 'N','primary--text':note.note_amount < 0}" class="text-md-center pr-2">{{note.note_user}}</td>
                            <td class="text-md-center">
                                <span @click="printNote(note,index)"  class="icon-medium-fill-base xs1 white--text info icon-print"></span>
                                
                            </td>
                        </tr>
                        <tr class="mini-input" v-if="notes.length ===  0">
                            <td colspan="5" class="text-md-center pr-2">
                                Tidak ada data
                            </td>
                        </tr>
                    </table>
                </v-layout>
                <v-layout style="border-top:1px dashed rgb(221,221,221)" row mt-1 mb-1></v-layout>
                <v-layout row mt-1 mb-1 pl-2 pr-2>
                    <v-btn class="text-md-center" @click="printInvoice()" color="teal" dark>
                        Invoice
                    </v-btn>
                    <v-btn class="text-md-center" @click="printKw()" color="print" dark>
                        Kwitansi
                    </v-btn>
                </v-layout>
            </v-card>
            <v-card >
                <v-layout row pa-2 align-center wrap >
                    <v-flex xs6>
                        <v-layout row>
                            <v-flex xs12>
                                <div class="label-tagihan text-xs-left">Total Tagihan</div>
                            </v-flex>
                        </v-layout>
                        <v-layout pt-1 row>
                            <v-flex xs9>
                                <div class="text-xs-left warning--text">Minimun DP ({{selectedpatient.mindp_percent}}%)</div>
                            </v-flex>
                            <v-flex xs3>
                                <div class="text-xs-right warning--text">{{convertMoney(selectedpatient.mindp_amount)}}</div>
                            </v-flex>
                        </v-layout>
                    </v-flex>
                    <v-flex xs6>
                       <div class="text-tagihan text-xs-right"><kbd>{{convertMoney(restbill)}}</kbd></div>
                    </v-flex>
                </v-layout>
                <v-layout style="border-top:1px dashed rgb(221,221,221)" row mt-1 mb-1></v-layout>
                <div v-for="(type, index) in types">
                    <v-layout row pt-2 pb-1 pl-2 align-center wrap >
                        <v-flex xs12>
                            <v-switch
                                v-model="type.chex"
                                @change="updateChx(type,index)"
                                :label="type.chexlabel"
                            ></v-switch>
                        </v-flex>
                    </v-layout>
                    <v-layout row pa-2 align-center wrap >
                        <v-flex xs2>
                            <div class="sub-title pl-2">{{type.leftlabel}}</div>
                        </v-flex>
                        <v-flex xs4>
                            <div  class="pa-2">
                                <input type="text" @change="updateTotal()" :class="{ 'disabled-background':type.chex === false }" :disabled="!type.chex"  v-model="type.leftvalue" class="input-plain text-xs-right font-weight-bold"/>
                            </div>
                        </v-flex>
                        <v-flex xs2>
                            <div class="sub-title pl-2">{{type.rightlabel}}</div>
                        </v-flex>
                        <v-flex xs4>
                            <div class="pa-2">
                                <input type="text" :class="{ 'disabled-background':type.chex === false, 'text-xs-right':type.code === 'CASH' }" :disabled="!type.chex" v-model="type.rightvalue" class="input-plain"/>
                            </div>
                        </v-flex>
                    </v-layout>
                    <v-layout style="border-top:1px dashed rgb(221,221,221)" row mt-1 mb-1></v-layout>
                </div>
                <v-layout row pa-2 align-center wrap >
                    <v-flex xs6>
                        <div class="label-tagihan text-xs-left">
                            <v-btn @click="pay()" color="warning" dark>
                                Bayar
                            </v-btn>
                        </div>
                    </v-flex>
                    <v-flex xs6>
                       <div class="text-tagihan text-xs-right"><kbd>{{convertMoney(totpay)}}</kbd></div>
                    </v-flex>
                </v-layout>
            </v-card>



            <template>
                <v-dialog
                v-model="xdialogpaysuccess"
                max-width="30%"
                persistent 
                >
                    <v-card>
                        <v-card-title
                            class="headline success pt-2 pb-2"
                            primary-title
                        >
                        <h4 style="color:#FFEBEE">Pembayaran Berhasil</h4>
                        </v-card-title>
                        <v-card-text class="pt-2 pb-2">
                            <v-layout row>
                                <v-flex xs12 d-flex>
                                    <v-layout row>
                                        <v-flex pb-1 xs12>
                                            <v-layout row>
                                                <v-flex pt-2 pr-2 v-html="xmsgpaysuccess" xs12>
                                                
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
                            <v-btn
                                flat
                                @click="doPrint()"
                            >
                            Print
                            </v-btn>
                            <v-btn
                                color="error"
                                flat
                                @click="closeDialogPaySuccess(false)"
                            >
                            Tutup
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </template>

            <template>
  
                <v-dialog
                v-model="xdialogdelete"
                persistent 
                max-width="30%"
                >
                    <v-card>
                        <v-card-title
                            :class="{ 'red':!_.isEmpty(xnotadelete), 'success':_.isEmpty(xnotadelete) }"
                            class="headline darken-1 pt-2 pb-2"
                            primary-title
                        >
                        <h4 style="color:#FFEBEE">
                        <span v-if="!_.isEmpty(xnotadelete)">Peringatan !</span>
                        <span v-if="_.isEmpty(xnotadelete)">Berhasil !</span>
                        </h4>
                        </v-card-title>
                        <v-card-text class="pt-2 pb-2">
                            <v-layout row>
                                <v-flex xs12 d-flex>
                                    <v-layout row>
                                        <v-flex pb-1 xs12>
                                            <v-layout row>
                                                <v-flex pt-2 pr-2 v-html="xmsgdelete" xs12>
                                                
                                                </v-flex>
                                            </v-layout>
                                            <v-layout v-if="!_.isEmpty(xnotadelete)" row>
                                                <v-flex pt-2 pr-2 xs12>
                                                    <input style="border: 1px solid black;padding: 5px;width: 100%;" type="text" placeholder="Catatan (*Wajib diisi)" v-model="xnotedelete" class="input-plain"/>
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
                            <v-btn
                                color="error"
                                v-if="!_.isEmpty(xnotadelete) && xnotedelete "
                                flat
                                @click="doDeleteNote()"
                            >
                            Yakin dong !
                            </v-btn>

                            <v-btn
                                v-if="!_.isEmpty(xnotadelete)"
                                color="primary"
                                flat
                                @click="xdialogdelete = false"
                            >
                            Tutup
                            </v-btn>
                            <v-btn
                                v-if="_.isEmpty(xnotadelete)"
                                color="primary"
                                flat
                                @click="closeDialogDelete()"
                            >
                            Tutup
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            
            </template>
            <one-dialog-print :title="printtitle" :width="printwidth" :height="500" :status="openprintnote" :urlprint="urlprintnote" @close-dialog-print="openprintnote = false"></one-dialog-print>
        </v-layout>
</template>

<style scoped>
.label-tagihan{
    text-align:left;
    font-size: 25px;
    font-family: open sans, tahoma, sans-serif;
    font-weight:700;
}

.sub-header{
    text-align:left;
    font-size: 18px;
    font-family: open sans, tahoma, sans-serif;
    font-weight:700;
}

.sub-title{
    text-align:left;
    font-size: 14px;
    font-family: open sans, tahoma, sans-serif;
    font-weight:700;
}

.text-tagihan{
    text-align:left;
    font-size: 42px;
    font-family: open sans, tahoma, sans-serif;
}

.disabled-background{
    background:#b7b7b7;
}

.input-cash{
    width: 100%;
    padding: 8px 14px;
    box-sizing: border-box;
    border: 2px solid grey;
    border-radius: 4px;
    font-size: 22px;
    font-weight:700;
    text-align:right;
}
.input-plain{
    width: 100%;
    padding: 4px 8px;
    box-sizing: border-box;
    border: 2px solid grey;
    border-radius: 4px;
    font-size: 14px;
}
.v-input, .v-input__slot, .v-messages{
    margin:0px;
    padding:0px;
    min-height: 0px; 
}
.v-input--selection-controls:not(.v-input--hide-details) .v-input__slot {
    margin-bottom: 0px;
}
table {
   font-family: arial, sans-serif;
   border-collapse: collapse;
   width: 100%;
   background:white;
   border: 0px;
}

th, td {
   border: 1px solid black;
   border-collapse: collapse;
   padding-top: 2px;
   padding-bottom: 2px;
}
table>tr>td {
   padding: 8px;
}
table>tr>td:first {
   padding-left:15px!important;
}
.mini-input .v-input{
    margin-top: 0px; 
}

.mini-input .v-input, .mini-input .v-input--selection-controls,.mini-input .v-input__slot{
    margin-top: 0px; 
    margin-bottom:0px;
    margin-left:3px;
}
.mini-input .v-messages{
    min-height:0px;
}

.border-bottom-dashed{
      border-bottom : 1px dashed rgba(0,0,0,.12);
}
</style>
<script>
module.exports = {
    components : {
        'one-field-verification' : httpVueLoader('../../common/oneFieldVerificationSupply.vue'),
        'one-dialog-print':httpVueLoader('../../common/oneDialogPrintX.vue')
    },
    data () {
      return {
        checkbox: true,
        radioGroup: 1,
        switchCash: true,
        switchDebit: false,
        switchKredit: false,
        dialog:false,
        urlprintnote:'',
        printtitle:'',
        printwidth:600
      }
    },
    mounted() {
      this.$store.dispatch("payment/lookup_type")
    },
    computed: {
        notes(){
            return this.$store.state.payment.notes
        },
        xdialogpaysuccess(){
            return this.$store.state.payment.dialog_pay_success
        },
        xmsgpaysuccess(){
            return this.$store.state.payment.paynumber
        },
        types() {
            return this.$store.state.payment.types
        },
        totpay() {
            return this.$store.state.payment.total_payment
        },
        restbill(){
            if(this.$store.state.patient.patients.length > 0){
                return this.$store.state.patient.selected_patient.totalbill - this.$store.state.patient.selected_patient.paid
            }
            else{
                return 0 
            }
            
        },
        xdialogdelete:{
            get() {
                return this.$store.state.payment.dialog_delete
            },
            set(val) {
                this.$store.commit("payment/update_dialog_delete",val)
            }
         },
         xmsgdelete(){
             return this.$store.state.payment.msg_delete
         },
         xnotadelete(){
             return this.$store.state.payment.nota_delete
         },
         xnotedelete:{
            get() {
                return this.$store.state.payment.note_delete
            },
            set(val) {
                this.$store.commit("payment/update_note_delete",val)
            }
         },
         openprintnote: {
            get() {
                return this.$store.state.payment.open_print_note
            },
            set(val) {
                this.$store.commit("payment/update_open_print_note",false)
            }
        },
        selectedpatient(){
            return this.$store.state.patient.selected_patient
        }
    },
    methods : {
        convertMoney(money){
            return one_money(money)
        },
        closeDialogPaySuccess(){
            let arrpatient = this.$store.state.patient.patients
            var idx = _.findIndex(arrpatient, item => item.T_OrderHeaderID === this.$store.state.patient.selected_patient.T_OrderHeaderID)
            this.$store.commit("payment/update_dialog_pay_success",false)
            this.$store.commit("patient/update_selected_patient",{})
            this.$store.dispatch("patient/search",{
                startdate:this.$store.state.patient.start_date,
                enddate:this.$store.state.patient.end_date,
                search: this.$store.state.patient.search,
                status: this.$store.state.patient.selected_status.value,
                current_page:this.$store.state.patient.current_page,
                lastidx:idx
            })
        },
        updateTotal(){
            var xval = this.$store.state.payment.types
            let xcash = _.filter(xval, {code: 'CASH'})
            let xother = _.filter(xval, type => type.code !== 'CASH')
            var valother = 0
            xother.forEach(function(obj){
                valother += parseInt(obj.leftvalue)
            })
            let restother = this.restbill - valother
            let xchange = parseInt(xcash[0].leftvalue) - restother 
            xcash[0].rightvalue = Math.max(0, xchange)
            let idxcash = _.findIndex(xval, item => item.code === 'CASH')
            xval[idxcash] = xcash[0]
            this.$store.commit("payment/update_types",{records :xval,total:xval.length })
            let totpaid = valother + ( parseInt(xcash[0].leftvalue) - Math.max(0, xchange) )
            this.$store.commit("payment/update_total_payment",totpaid)
        },
        pay(){
            var xval = this.$store.state.payment.types
            var valpay = 0
            let xcash = _.filter(xval, {code: 'CASH'})
            xval.forEach(function(obj){
                valpay += parseInt(obj.leftvalue)
            })
            if(valpay > 0 || xcash[0].leftvalue ){
                let prm = {orderid:this.$store.state.patient.selected_patient.T_OrderHeaderID,payments:this.$store.state.payment.types}
                this.$store.dispatch("payment/pay",prm)
            }
        },
        deleteNote(note,idx){
            this.$store.commit("payment/update_note_delete","")
            this.$store.commit("payment/update_nota_delete",note)
            let xmsg = "Yakin , mau hapus nota nomor <span style='color:red'>"+note.note_number+"</span> ?"
            this.$store.commit("payment/update_msg_delete",xmsg)
            this.$store.commit("payment/update_dialog_delete",true)
        },
        doDeleteNote(){
            let prm = {catatan:this.$store.state.payment.note_delete,nota:this.$store.state.payment.nota_delete}
            this.$store.dispatch("payment/delete_note",prm)
        },
        closeDialogDelete(){
            let arrpatient = this.$store.state.patient.patients
            var idx = _.findIndex(arrpatient, item => item.T_OrderHeaderID === this.$store.state.patient.selected_patient.T_OrderHeaderID)
            this.$store.commit("payment/update_dialog_delete",false)
            this.$store.commit("patient/update_selected_patient",{})
            this.$store.dispatch("patient/search",{
                startdate:this.$store.state.patient.start_date,
                enddate:this.$store.state.patient.end_date,
                search: this.$store.state.patient.search,
                status: this.$store.state.patient.selected_status.value,
                lastidx:idx
            })
        },
        updateChx(val,idx){
            let xobj = this.$store.state.payment.types
            xobj[idx].leftvalue = 0
            xobj[idx].rightvalue = 0
            this.$store.commit("payment/update_types",{records :xobj,total:xobj.length })
            this.updateTotal()
        },
        printNote(val,idx){
            this.printwidth = 600
            this.printtitle = ""
            let user = one_user()
            var rpt = 'rpt_t_003'
            if(val.note_amount < 0)
                rpt = 'rpt_t_004'
            this.urlprintnote = "/birt/run?__report=report/one/fo/"+rpt+".rptdesign&__format=pdf&username="+user.M_UserUsername+"&PID="+val.note_id
            this.$store.commit("payment/update_open_print_note",true)
        },
        doPrint(){
            this.printtitle = ""
            this.closeDialogPaySuccess()
            let user = one_user()
            let payments = this.$store.state.payment.last_payments
            let xcash = _.filter(payments, {code: 'CASH'})
            var rpt = 'rpt_t_003'
            if(xcash[0].leftvalue < 0)
                rpt = 'rpt_t_004'
            this.urlprintnote = "/birt/run?__report=report/one/fo/"+rpt+".rptdesign&__format=pdf&username="+user.M_UserUsername+"&PID="+this.$store.state.payment.idx
            this.$store.commit("payment/update_open_print_note",true)
        },
        printKw(){
            this.printwidth = 800
            this.printtitle = ""
            let idx = this.$store.state.patient.selected_patient.T_OrderHeaderID
            let user = one_user()
            this.urlprintnote = "/birt/run?__report=report/one/fo/rpt_t_002.rptdesign&__format=pdf&username="+user.M_UserUsername+"&PID="+idx
            this.$store.commit("payment/update_open_print_note",true)
        },
        printInvoice(){
            this.printwidth = 800
            this.printtitle = ""
            let idx = this.$store.state.patient.selected_patient.T_OrderHeaderID
            let user = one_user()
            this.urlprintnote = "/birt/run?__report=report/one/fo/rpt_t_001.rptdesign&__format=pdf&username="+user.M_UserUsername+"&PID="+idx
            this.$store.commit("payment/update_open_print_note",true)
        }

   }
}
</script>
