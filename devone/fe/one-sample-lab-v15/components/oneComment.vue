<template>
<v-dialog
   v-model="show"
   width="40%"
   >
   <v-card>
       <v-card-title
           class="headline white--text primary"
           primary-title
           v-html="patient_info"
       >
       </v-card-title>

       <v-card-text>
               <ul class="mb-2"> 
                  <li>
                  Order {{order_tgl}} by {{order_user}}
                  <div v-if="fo_note != ''" class="mb-2 pa-2 " style="background:rgba(230, 255, 255)" > 
                     {{fo_note}}
                  </div>
                  </li>
                  <li>
                  Screening {{ver_tgl}} by {{ver_user}}
                  <div v-if="ver_note != ''" class="mb-2 pa-2 " style="background:rgba(230, 255, 255)" > 
                     {{ver_note}}
                  </div>
                  </li>
               </ul>
               <v-layout row>
                   <v-flex xs12>
                       <v-textarea
                           outline
                           label="Catatan"
                           v-model="comment"
                       ></v-textarea>
                   </v-flex>
               </v-layout>
               <v-layout mb-2 row>
                   <v-flex xs12>
		     <v-progress-linear :active="loading" :indeterminate="true">
      		     </v-progress-linear>
                  </v-flex>
               </v-layout>
       </v-card-text>

       <v-divider></v-divider>

       <v-card-actions>
       <v-spacer></v-spacer>
       <v-btn
           color="grey"
           dark
           flat
           text
           @click="close_dialog"
       >
           Tutup
       </v-btn>
       <v-btn
           color="primary"
           dark
           flat
           text
           @click="saveComment()"
       >
           Simpan
       </v-btn>
       </v-card-actions>
   </v-card>
</v-dialog>
</template>

<script>
module.exports = {
   methods: {
      async saveComment() {
         await this.$store.dispatch("comment/save")
         this.$store.commit("comment/update_show",false)
      },
      close_dialog() {
         this.$store.commit("comment/update_show",false)
      }
   },
   computed: {
      ver_user() {
         let p = this.$store.state.comment.history
         return  p.verStaffName
      },
      ver_tgl() {
         let p = this.$store.state.comment.history
         return  moment(p.verDate).format('DD.MM.YYYY HH:mm')
      },
      ver_note() {
         let p = this.$store.state.comment.history
         if ( p.verNote == null ) return ''
         return  p.verNote
      },
      fo_note() {
         let p = this.$store.state.comment.history
         return  p.T_OrderHeaderFoNote
      },
      order_tgl() {
         let p = this.$store.state.comment.history
         return moment(p.T_OrderHeaderDate).format('DD.MM.YYYY HH:mm')
      },
      order_user() {
         let p = this.$store.state.comment.history 
         return p.foStaffName
      },
      patient_info() {
         let patient = this.$store.state.comment.patient 
         return patient.T_OrderHeaderLabNumber +
            "<span style='color:yellow;margin-left:5px;margin-right:5px;padding:2px;'>[" + patient.T_OrderHeaderLabNumberExt + "]</span>" +
            "&nbsp;&nbsp;&nbsp;" + patient.M_PatientName 
      },
      loading() {
         return this.$store.state.comment.loading
      },
      show: {
         get() { return this.$store.state.comment.show },
         set(v) { this.$store.state.commit("comment/update_show",v)}
      },
      comment: {
         get() { 
            let note = this.$store.state.comment.patient.T_OrderHeaderSamplingNote 
            if ( note == null ) return ''
            return note
            },
         set(v) { 
            let patient = this.$store.state.comment.patient 
            patient.T_OrderHeaderSamplingNote = v 
            this.$store.commit("comment/update_patient",patient)
         }
      }
   },
   
}
</script>
