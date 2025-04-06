   <template>
      <div>
         <v-dialog
            v-model="dialog_details"
            max-width="98%"
            persistent
            scrollable
         >
            

            <v-card>
            <v-card-title
               class="title grey lighten-2"
            >
               Informasi detail
            </v-card-title>

            <v-card-text >
               <v-layout row  style="width:98%">
                  <v-flex xs12>
                     <p class="subheader grey--text">DATA PASIEN</p>
                  </v-flex>
               </v-layout>
               <v-layout align-center row>
                  <v-flex xs12>
                     <p class="body-1 mb-1 font-weight-black"><v-icon small>bookmark</v-icon> {{selected_detail.noreg}}</p>
                  </v-flex>
               </v-layout>
               <v-layout align-center row>
                  <v-flex xs12>
                     <p>{{selected_detail.name}}</p>
                  </v-flex>
               </v-layout>
               <v-layout align-center row>
                  <v-flex xs12>
                     <p class="body-1 mb-1 font-weight-black"><v-icon small>location_on</v-icon> {{selected_detail.district}}</p>
                  </v-flex>
               </v-layout>
               <v-layout align-center row>
                  <v-flex xs12>
                     <p class="mb-1">{{selected_detail.destination}}</p>
                     <p class="orange--text">Catatan : {{selected_detail.note}}</p>
                  </v-flex>
               </v-layout>
               <v-divider></v-divider>
               <v-layout align-center row>
                  <v-flex pa-0 ml-0 xs12>
                     <v-radio-group v-if="selected_detail.status !== 'D'" v-model="selected_receive_status">
                        <template v-slot:label>
                           <div class="body-1 grey--text">PILIH STATUS </div>
                        </template>
                        <p v-if="error_status" class="caption red--text mb-1"> ... statusnya ya jangan lupa</p>
                        <v-radio v-for="xradio in radios" @change="selectStatus(xradio)" :value="xradio.id">
                           <template v-slot:label>
                              <div class="body-1">{{xradio.name}}</div>
                           </template>
                        </v-radio>
                     </v-radio-group>
                     
                     <p class="mb-0" v-if="selected_detail.status === 'D'">
                        <v-btn small block class="ml-0 mb-0 mt-2" color="teal"  v-if="selected_detail.status === 'D'" outline>
                           <v-icon small color="teal">check_box</v-icon> 
                           &nbsp;&nbsp;{{selected_detail.receiver_status_name}}
                        </v-btn>
                     </p>
                     <p class="mb-2" v-if="selected_detail.status === 'D'">
                        <v-btn block class="ml-0" color="teal" dark small>
                           <v-icon small dark>alarm</v-icon> 
                           &nbsp;&nbsp;{{selected_detail.received_time}}
                        </v-btn>
                     </p>
                  </v-flex>
               </v-layout>
               <v-layout row>
                  <v-flex xs12>
                     <v-text-field
                        :disabled="selected_receive_status === '1'"
                        label="Penerima"
                        placeholder="Isikan penerima"
                        v-model="selected_detail.receiver"
                        hide-details
                        box
                     ></v-text-field>
                     <p v-if="error_receiver" class="caption red--text mb-1"> ... diisi ya penerimanya</p>
                  </v-flex>
               </v-layout>
               <v-layout row>
                  <v-flex xs12>
                     <v-text-field
                        v-if="selected_detail.status !== 'D' && selected_detail.rest !== '0' && selected_detail.flag_bill === 'N'"
                        suffix="Rp"
                        placeholder="Isikan pembayaran"
                        single-line
                        v-model="selected_detail.pay"
                        reverse
                        hide-details
                        box
                     ></v-text-field>
                     <kbd class="mt-1 mono caption" v-if="selected_detail.status === 'D' && (selected_detail.pay !== '0' || selected_detail.pay !== 0)" 
                        color="teal">BAYAR : Rp {{convertMoney(selected_detail.pay)}}</kbd>
                  </v-flex>
               </v-layout>
               <v-layout v-if="selected_detail.status !== 'D' && ( selected_detail.rest !== '0' ) && selected_detail.flag_bill === 'N'" row>
                  <v-flex xs12>
                     <p class="mt-1 caption red--text">Kurang bayar : Rp {{convertMoney(selected_detail.rest)}}</p>
                  </v-flex>
               </v-layout>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions>
               <v-btn
                  block
                  color="danger"
                  flat
                  @click="dialog_details = false"
               >
                  TUTUP
               </v-btn>
               <v-spacer></v-spacer>
               <v-btn
                  v-if="selected_detail.status !== 'D'"
                  block
                  color="primary"
                  flat
                  @click="serahkan()"
               >
                  SELESAI
               </v-btn>
            </v-card-actions>
            </v-card>
         </v-dialog>
         <v-layout row>
            <v-flex xs12 sm6 offset-sm3>
               <v-card>
               <v-toolbar color="teal" dark>
                  <v-toolbar-side-icon><v-icon>keyboard_arrow_left</v-icon></v-toolbar-side-icon>
                  <v-toolbar-title class="text-xs-center">{{selected_spk.xnumber}}</v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-menu bottom left>
                     <template v-slot:activator="{ on }">
                     <v-btn
                        dark
                        icon
                        v-on="on"
                     >
                        <v-icon>more_vert</v-icon>
                     </v-btn>
                     </template>

                     <v-list>
                     <v-list-tile
                        v-for="(xspk,spk_idx) in spks"
                        @click="select_spk(xspk)"
                     >
                        <v-list-tile-title>{{ xspk.xnumber }}</v-list-tile-title>
                     </v-list-tile>
                     </v-list>
                  </v-menu>
               </v-toolbar>

               <v-list subheader v-for="(detail,idx) in selected_spk.details" three-line>
                  <v-subheader>{{detail.delivery_name}}</v-subheader>
                  <v-divider></v-divider>
                  <template v-for="(item, index) in detail.details">
                     <v-list-tile
                     ripple
                     @click="openDetails(item,spk_idx,idx,index)"
                     style="height:auto;min-height:88px"
                     >
                     <v-list-tile-content>
                        <v-list-tile-title class="body-2">{{item.noreg}} {{item.name}}</v-list-tile-title>
                        <v-list-tile-sub-title class="body-1">
                           <span class='text--primary'>{{item.district}} </span> &mdash; <span class='grey--text caption'>{{item.destination}}</span>
                        </v-list-tile-sub-title>
                        <v-list-tile-sub-title  class="caption orange--text">
                           Catatan : {{item.note}}
                        </v-list-tile-sub-title>
                     </v-list-tile-content>

                     <v-list-tile-action>
                        <v-list-tile-action-text v-if="item.status !== 'D' && item.rest !== '0' && item.flag_bill === 'N'" class="text-right mono red--text caption">
                           {{convertMoney(item.rest)}}
                        </v-list-tile-action-text>
                        <v-spacer></v-spacer>
                        <v-icon v-if="item.status === 'D'"
                           color="teal"
                        >
                           done
                        </v-icon>
                        <v-icon v-if="item.status !== 'D'"
                           color="grey"
                        >
                           remove
                        </v-icon>
                     </v-list-tile-action>

                     </v-list-tile>
                     <v-divider
                        v-if="index + 1 < items.length"
                        :key="index"
                     ></v-divider>
                  </template>
               </v-list>

               </v-card>
            </v-flex>
         </v-layout>
      </div>
</template>

<style scoped>
.v-list--three-line .v-list__tile {
   height:auto;
   min-height: 88px;
}
</style>

<script>
module.exports = {
   components : {
      'one-dialog-info':httpVueLoader('../../common/oneDialogInfo.vue'),
      'one-dialog-alert':httpVueLoader('../../common/oneDialogAlert.vue')
   },
   mounted() {
      this.$store.dispatch("patient/lookup_statuses",{})
      this.$store.dispatch("patient/search",{})
   },
   methods : {
      convertMoney(money){
            return one_money(money)
        },
      openDetails(item,spk_index,idx,detail_idx){
         //console.log(spk_index)
         this.error_receiver = false
         this.error_status = false
         this.spk_idx = spk_index
         console.log(this.spk_idx)
         this.idx = idx
         console.log(idx)
         this.detail_idx = detail_idx
         console.log(detail_idx)
         this.selected_detail = item
         this.radios = this.$store.state.patient.statuses
         this.selected_receive_status = null
         //this.selected_receive_status = item.receiver_status_id
         this.dialog_details = true
      },
      select_spk(spk){
         this.selected_spk = spk
      },
      selectStatus(value){
         console.log(value)
         //var value = this.selected_receive_status
         if(value.id === '1' || value.id === 1){
            this.selected_detail.receiver = this.selected_detail.name
         }
         else{
            this.selected_detail.receiver = ''
            if(value.id !== '2' || value.id === 2){
               this.selected_detail.receiver = '-'
            }
         }
         this.selected_detail.receiver_status_id = value.id
         this.selected_detail.receiver_status_name = value.name
      },
      serahkan(){
         this.error_receiver = false
         this.error_status = false
         var param = this.selected_detail
         if( parseInt(param.receiver_status_id) !== 0){
            var goon = true
            var msg = ''
            if( param.receiver_status_id == '2' || param.receiver_status_id !== 2){
               if(param.receiver === ''){
                  goon = false
                  this.error_receiver = true
               }
            }
            param.spk_idx = this.spk_idx
            param.last_idx = this.idx
            param.last_detail_idx = this.detail_idx
            if(goon)
               this.$store.dispatch("patient/serahkan",param)
         }
         else{
             this.error_status = true
         }
      }
   },
   computed: {
      xtotalpatients() {
         return this.$store.state.patient.total_patients
      },
      spks: {
         get() {
            return this.$store.state.patient.patients
         },
         set(val) {
            this.$store.commit("patient/update_patients",val)
         }
      },
      selected_spk: {
         get() {
            return this.$store.state.patient.selected_patient
         },
         set(val) {
            this.$store.commit("patient/update_selected_patient",val)
         }
      },
      filters: {
         get() {
            return this.$store.state.patient.filters
         },
         set(val) {
            this.$store.commit("patient/update_filters",val)
         }
      },
      dialog_details: {
         get() {
            return this.$store.state.patient.dialog_details
         },
         set(val) {
            this.$store.commit("patient/update_dialog_details",val)
         }
      },
   },
   data () {
      return {
        //selected_spk :{},
        error_receiver:false,
        error_status:false,
        spk_idx:0,
        idx:0,
        detail_idx:0,
        switchMe: true,
        radios:[],
        //dialog_details:false,
        selected_radio: [2],
        selected_receive_status:null,
        selected_detail:{},
        spk_lists: [
            { title: 'Click Me' },
            { title: 'Click Me' },
            { title: 'Click Me' },
            { title: 'Click Me 2' }
         ],
        items: [
           {
              district:"Tamansari",
              destination:"Jl. Muararajeun Tengah No. 23",
              noreg:"210220001DA",
              name:"Fajri Hardhita Murti",
              status_payment:"Y",
              rest:"1500000",
              pay:"1500000",
              status:"D",
              note:"-"
           },
          {
              district:"Buahbatu",
              destination:"Jl. Slamet Riyadi No. 203",
              noreg:"210220002DA",
              name:"Agus Rahman",
              status_payment:"N",
              rest:"200000",
              pay:"0",
              status:"S",
              note:"-"
           },
           {
              district:"Buahbatu",
              destination:"Jl. Slamet Riyadi No. 203",
              noreg:"210220003DA",
              name:"Wika Setyawati",
              status_payment:"Y",
              rest:"0",
              pay:"0",
              status:"D",
              note:"Titipkan saja ke rumah sebelah kanan, namanya bu wati"
           },
        ]
      }
   }
}
</script>
