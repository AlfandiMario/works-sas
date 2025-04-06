<template>
 <v-layout >
 <v-flex xs12 >
 <v-card  class="mb-2" color="white"  >
    <v-toolbar  color="blue lighten-3" dark height="50px">
    <v-toolbar-title>User Group : {{xusergroup.name}}</v-toolbar-title>
    <v-spacer></v-spacer>
    <v-btn v-if="xusergroup.name !== '[ Belum memilih User Group ]'" @click="openFormUser(0)" icon>
      <v-icon>library_add</v-icon>
    </v-btn>
  </v-toolbar>

  <v-layout row wrap>
    <v-flex class="border-bottom-dashed" xs12 pt-2 pl-4 pr-4 pb-4>
          <table>
              <tr>
                <th width="25%" class="text-md-center pt-2 pb-2">USERNAME</th>
                <th  width="35%" class="text-md-center pt-2 pb-2">STAFF NAME</th>
                <th  class="text-md-center pt-2 pb-2">AKSI</th>
              </tr>
              <tr v-if="users.length > 0 " v-for="(user,idx) in users">
                <td class="text-md-left pl-3">{{user.username}}</td>
                <td class="text-md-center">{{user.staffname}}</td>

                <td align="center" class="text-md-center">
                  <v-btn @click="editFormUser(user)" depressed small color="primary"><v-icon>edit</v-icon></v-btn>
                  <v-btn @click="deleteUser(user)" depressed small color="error"><v-icon>delete</v-icon></v-btn>
                </td>
              </tr>
              <tr v-if="users.length === 0"><td align="center" style="height:50px;text-align:center" colspan="8">Belum ada data</td> </tr>
          </table>
    </v-flex>
  </v-layout>
   <template>

    <v-dialog
      v-model="dialogdeletealertuser"
      max-width="30%"
    >
        <v-card>
            <v-card-title
                class="headline grey lighten-2 pt-2 pb-2"
                primary-title
            >
            Peringatan !
            </v-card-title>
            <v-card-text class="pt-2 pb-2">
                <v-layout row>
                    <v-flex xs12 d-flex>
                        <v-layout row>
                            <v-flex pb-1 xs12>
                                <v-layout row>
                                    <v-flex pt-2 pr-2 xs12>
                                       {{msgalertuser}}
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
                    color="primary"
                    flat
                    @click="dialogdeletealertuser = false"
                >
                Tutup
                </v-btn>
                <v-btn
                    color="primary"
                    flat
                    @click="closeDeleteAlertUser()"
                >
                Yakin lah
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

</template>


  <template>
    <v-layout row justify-center>
      <v-dialog v-model="dialoguser" persistent max-width="600px">
        <v-card>
          <v-card-title>
            <span class="headline">Form User</span>
          </v-card-title>
          <v-card-text class="pt-0 pb-0">
          <v-form
              ref="formusergroupuser"
              v-model="validuser"
              lazy-validation
            >
            <v-layout wrap>
                <v-flex xs12>
                  USER GROUP : {{xusergroup.name}}
                </v-flex>
                <v-flex xs12>
                  <v-text-field label="User Name" v-model="username"   ></v-text-field>
                </v-flex>



                <v-flex xs12>
                   <v-text-field   label="Password" v-model="password" type="password" ></v-text-field>
                 </v-flex>

                 <v-flex xs12>
                 <v-select :search-input.sync="searchStaff" item-text="M_StaffName" return-object :items="xstaffs" autocomplete  v-model="xstaff" label="Staff Name" ></v-select>
                 </v-flex>

                 <!--<v-flex xs12>
                 <v-select item-text="T_SampleStationName" return-object :items="xsamplestationes"  v-model="xsamplestation" label="Sample Station" ></v-select>
                 </v-flex>-->

                 <v-flex xs12>
                 <v-select item-text="R_ReportGroupName" return-object :items="xreportes"  v-model="xreport" label="Report Group" ></v-select>
                 </v-flex>

                 <!--<v-flex xs12>
                 <v-checkbox v-model="iscoordinator" label="Coordinator ?"></v-checkbox>
                 </v-flex>-->

                <v-flex>
                  <p v-for="(xerror,idx) in xerrors" class="error pl-2 pr-2" style="color:#fff">{{xerror.msg}}</p>
                </v-flex>
            </v-layout>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" flat @click="updateDialogFormUser()">Tutup</v-btn>
            <v-btn color="blue darken-1" flat @click="saveFormUser()">Simpan</v-btn>
          </v-card-actions>
          </v-form>
        </v-card>
      </v-dialog>
    </v-layout>
  </template>

  <template>
    <v-layout row justify-center>
      <v-dialog v-model="dialoguseredit" persistent max-width="600px">
        <v-card>
          <v-card-title>
            <span class="headline">Form User</span>
          </v-card-title>
          <v-card-text class="pt-0 pb-0">
          <v-form
              ref="formusergroupuser"
              v-model="validuser"
              lazy-validation
            >
            <v-layout wrap>

                <v-flex xs12>
                <v-select item-text= "M_UserGroupName" return-object :items="xusergroupnames"
                          item-value= "M_UserGroupID"
                   v-model="xusergroupname" label="User Group" ></v-select>
                </v-flex>
                <v-flex xs12>
                  <v-text-field label="User Name" v-model="username"   ></v-text-field>
                </v-flex>



                <v-flex xs12>
               <v-select :search-input.sync="searchStaff" item-text="M_StaffName" return-object :items="xstaffs" autocomplete  v-model="xstaff" label="Staff Name" ></v-select>
                </v-flex>

                
                 <v-flex xs12>
                 <v-select item-text="R_ReportGroupName" return-object :items="xreportes"  v-model="xreport" label="Report Group" ></v-select>
                 </v-flex>



                

                <v-flex>
                  <p v-for="(xerror,idx) in xerrors" class="error pl-2 pr-2" style="color:#fff">{{xerror.msg}}</p>
                </v-flex>
            </v-layout>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="blue darken-1" flat @click="closeDialogEditUser()">Tutup</v-btn>
            <v-btn color="blue darken-1" flat @click="saveEditUser()">Simpan</v-btn>
          </v-card-actions>
          </v-form>
        </v-card>
      </v-dialog>
    </v-layout>
  </template>



 </v-card>
 </v-flex>



</v-layout>
</template>

<style scoped>
.searchbox .v-input.v-text-field .v-input__slot{
   min-height:60px;
}
.searchbox .v-btn {
   min-height:60px;
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

</style>

<script>
module.exports = {
   data() {
      return {
        searchStaff: '',
         query: "",
         items: [],
         headers: [
            {
               text: "USER NAME",
               align: "Center",
               sortable: false,
               value: "username",
               width: "30%",
               class: " blue lighten-4"
            },
            {
               text: "STAFF NAME",
               align: "Center",
               sortable: false,
               value: "staffname",
               width: "30%",
               class: "blue lighten-4"
            },
            {
               text: "PASSWORD",
               align: "Center",
               sortable: false,
               value: "password",
               width: "30%",
               class: "blue lighten-4"
            },

            {
               text: "Action",
               align: "Center",
               sortable: false,
               value: "status",
               width: "15%",
               class: "blue lighten-4"
            }
         ],
          isLoading: false,
          color:"success",
          validuser:false,
          xid:0,
          username:"",

          staffname:"",
          password:"",
          iscoordinator:"",
          dialogdeletealertuser:false,
          msgalertuser:""
      };
   },
   computed: {
      dialoguseredit() {
          return this.$store.state.user.dialog_form_user_edit
      },
      users() {
          return this.$store.state.user.users
      },
      xusergroup(){
        return this.$store.state.usergroup.selected_usergroup
      },
       dialoguser(){
        return this.$store.state.user.dialog_form_user
      },
      xerrors() {
          return this.$store.state.user.errors
      },
      xsamplestationes() {
          return  this.$store.state.usergroup.samplestations
      },
      xsamplestation:{
          get() {
              return this.$store.state.user.selected_samplestation
          },
          set(val) {
              var xval = val
              if ( val.T_SampleStationID == undefined ) {
                 for(var i=0; i< this.xsamplestationes.length ; i++ ) {
                    var xs = this.xsamplestationes[i]
                    if (xs.T_SampleStationID == val ) {
                       xval = xs
                    }
                 }
              }
              this.$store.commit("user/update_selected_samplestation",xval)
          }
      },
      xstaffs() {
          return  this.$store.state.usergroup.staffs
      },
      xstaff:{
          get() {
              return this.$store.state.user.selected_staff
          },
          set(val) {
              var xval = val
              if ( val.M_StaffID == undefined ) {
                 for(var i=0; i< this.xstaffs.length ; i++ ) {
                    var xs = this.xstaffs[i]
                    if (xs.M_StaffID == val ) {
                       xval = xs
                    }
                 }
              }
              this.$store.commit("user/update_selected_staff",xval)
          }
      },
      xusergroupnames() {
          return  this.$store.state.usergroup.usergroupnames
      },
      xusergroupname:{
          get() {
              return this.$store.state.user.selected_usergroupname
          },
          set(val) {
              var xval = val
              if ( val.M_UserGroupID == undefined ) {
                 for(var i=0; i< this.xusergroupnames.length ; i++ ) {
                    var xs = this.xusergroupnames[i]
                    if (xs.M_UserGroupID == val ) {
                       xval = xs
                    }
                 }
              }
             this.$store.commit("user/update_selected_usergroupname",xval)
          }
      },
      xreportes() {
          return  this.$store.state.usergroup.reports
      },
      xreport:{
          get() {
              return this.$store.state.usergroup.selected_report
          },
          set(val) {
              var xval = val
              if ( val.R_ReportGroupID== undefined ) {
                 for(var i=0; i< this.xreportes.length ; i++ ) {
                    var xs = this.xreportes[i]
                    if (xs.R_ReportGroupID== val ) {
                       xval = xs
                    }
                 }
              }
              this.$store.commit("usergroup/update_selected_report",xval)
          }
      }


  },
  methods : {
    updateDialogFormUser(){
       this.$store.commit("user/update_dialog_form_user",false)
    },
    openFormUser(val){
      this.xid = val
      this.username = ''

      this.password = ''
      this.xstaff = ''
      this.xsamplestation = ''
      this.xreport = ''
      this.iscoordinator = ''
      this.$store.commit("user/update_dialog_form_user",true)
    },
    editFormUser(val){
      this.$store.commit("user/update_error_username",false)

      this.$store.commit("user/update_error_password",false)
      this.$store.commit("user/update_error_xstaff",false)
      this.$store.commit("user/update_error_xsamplestation",false)
      this.$store.commit("user/update_error_xreport",false)
      this.$store.commit("user/update_error_xusergroupname",false)
      this.$store.commit("user/update_error_iscoordinator",false)
      this.xid = val.id
      this.username = val.username

      this.password = val.password
      this.xstaff = val.xstaff
      this.xsamplestation = val.xsamplestation
      this.xreport = val.xreport
      this.xusergroupname = val.usergroupid
      this.iscoordinator = val.iscoordinator === 'N'?false:true
      this.$store.commit("user/update_dialog_form_user_edit",true)
    },
    closeDialogEditUser() {
      this.$store.commit("user/update_dialog_form_user_edit",false)
    },
    saveEditUser() {
      this.$store.dispatch("user/save_edit",{
            xid:this.xid,
            usergroupid:this.$store.state.usergroup.selected_usergroup.id,
            usergroupname:this.$store.state.usergroup.selected_usergroup.name,
            username:this.username,

            xstaff:this.xstaff,
            xsamplestation:this.xsamplestation,
            xreport:this.xreport,
            xusergroupname:this.xusergroupname,
            iscoordinator: this.iscoordinator === true ?"Y":"N"
          })
    },
    saveFormUser(){
      this.$store.dispatch("user/save",{
            xid:this.xid,
            usergroupid:this.$store.state.usergroup.selected_usergroup.id,
            usergroupname:this.$store.state.usergroup.selected_usergroup.name,
            username:this.username,

            password:this.password,
            xstaff:this.xstaff,
            xsamplestation:this.xsamplestation,
            xreport:this.xreport,
            iscoordinator: this.iscoordinator === true ?"Y":"N"
          })
    },
    updateAlert_success(val){
      this.$store.commit("usergroup/update_alert_success",val)
    },
    deleteUser(data){
      this.xid = data.id
      this.msgalertuser = "Yakin, mau hapus user ?"
      this.dialogdeletealertuser = true
    },
    closeDeleteAlertUser(){
      this.$store.dispatch("user/delete",{
            xid:this.xid,
            usergroupid:this.$store.state.usergroup.selected_usergroup.id,
            usergroupname:this.$store.state.usergroup.selected_usergroup.name,
            username:this.username
          })
      this.dialogdeletealertuser = false
    }
  }
}
</script>
