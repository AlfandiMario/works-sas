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

       <v-card-text style="font-size:16px;">
               
               <ul  >
                   <li v-if="have_note">
                   Catatan FO <span style="color:brown;padding:2px">[{{user}}]</span>
                     <div>
                        {{fo_note()}}
                     </div>
                   </li>
                   <li v-if="have_ver_note">
                   Catatan FO Verifikasi<span style="color:brown;padding:2px">[{{ver_user}}]</span>
                     <div>
                        {{ver_note()}}
                     </div>
                   </li>
                   <li v-for="req in requirements" >
                     {{get_position(req)}}
                      <div >
                         {{get_detail(req)}}
                     </div>
                   </li>
               </ul>
       </v-card-text>

      <v-progress-linear :active="loading" :indeterminate="true">
      </v-progress-linear>
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
       </v-card-actions>
   </v-card>
</v-dialog>
</template>

<script>
module.exports = {
   methods: {
      get_position(req) {
         if( req.Nat_PositionName == null ) return ''
         return req.Nat_PositionName.toUpperCase()
      },
      get_detail(req) {
         return req.Requirement
      },
      close_dialog() {
         this.$store.commit("req/update_show",false)
      },
      fo_note() {
         return this.$store.state.req.note.T_OrderHeaderFoNote
      },
      ver_note() {
         return this.$store.state.req.note.T_OrderHeaderVerificationNote
      },
   },
   computed: {
      user() {
         return this.$store.state.req.note.Fo_User
      },
      have_note() {
         let note = this.$store.state.req.note.T_OrderHeaderFoNote 
         if (note == null ) return false 
         return note != ''
      },
      have_ver_note() {
         let note = this.$store.state.req.note
         if ( note.T_OrderHeaderVerificationNote == null ) return false 
         return note.T_OrderHeaderVerificationNote != '' 
      },
      ver_user() {
         return this.$store.state.req.note.Ver_User
      },
      requirements() {
         return this.$store.state.req.requirements 
      },
      patient_info() {
         let patient = this.$store.state.req.patient 
         return patient.T_OrderHeaderLabNumber +
            "<span style='color:yellow;margin-left:5px;margin-right:5px;padding:2px;'>[" + patient.T_OrderHeaderLabNumberExt + "]</span>" +
            "&nbsp;&nbsp;&nbsp;" + patient.M_PatientName 
      },
      loading() {
         return this.$store.state.req.loading
      },
      show: {
         get() { return this.$store.state.req.show},
         set(v) { this.$store.commit("req/update_show",v)}
      },
      reqs() {
         return this.$store.state.req.requirements
      }
   },
   
}
</script>
