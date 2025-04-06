<template>
  <v-layout>
    <v-dialog v-model="dialogdeletealert" max-width="30%">
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
                      {{ msgalert }}
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
          <v-btn color="primary" flat @click="dialogdeletealert = false">
            Tutup
          </v-btn>
          <v-btn color="primary" flat @click="closeDeleteAlert()">
            Yakin lah
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>



    <v-flex xs12>
      <v-card class="scroll-container" style="/*max-height:645px;overflow: auto;*/">
        <v-toolbar color="blue lighten-3" dark height="50px">
          <v-toolbar-title>USER GROUP</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn @click="openFormUsergroup()" icon>
            <v-icon>library_add</v-icon>
          </v-btn>
        </v-toolbar>
        <v-snackbar v-model="snackbar" :color="color" :timeout="5000" :multi-line="false" :vertical="false" :top="true">
          {{ msgsnackbar }}
          <v-btn flat @click="updateAlert_success(false)">
            Tutup
          </v-btn>
        </v-snackbar>
        <v-layout row style="background:#bbdefb;padding-top:5px;" justify-left>
          <v-list-tile>
            <input type="text" v-model="xsearch" class="textinput" placeholder="Cari ..." />
            </v-list-tile-content>
          </v-list-tile>
        </v-layout>
        <v-divider></v-divider>
        <div v-for="(vs, index) in vusergroups">

          <v-layout pa-2 v-if="isSelected(vs)" row>
            <v-flex xs12 @click="selectMe(vs)">
              <v-layout row>
                <v-flex class="boxsolid" xs10>
                  <v-tooltip right>
                    <template v-slot:activator="{ on }">
                      <span v-html="subname(vs.description)" v-on="on"></span>
                    </template>
                    <span>{{ vs.name }}</span>
                  </v-tooltip>
                </v-flex>
                <v-flex class="boxsolid text-center" style="padding-top:10px" pl-2 pb-2 pr-2 xs2>
                  <v-layout row align-center justify-space-between>
                    <v-icon style="color:#ffffff" @click="editUsergroup(vs)">edit</v-icon>
                    <v-icon style="color:#ffffff" @click="deleteUsergroup(vs)">clear</v-icon>
                  </v-layout>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>

          <v-layout pa-2 v-if="!isSelected(vs)" row>
            <v-flex xs12 @click="selectMe(vs)">
              <v-layout row>
                <v-flex class="boxoutline" xs10>
                  <v-tooltip right>
                    <template v-slot:activator="{ on }">
                      <span v-html="subname(vs.description)" v-on="on"></span>
                    </template>
                    <span>{{ vs.name }}</span>
                  </v-tooltip>
                </v-flex>
                <v-flex class="boxoutline text-center" style="padding-top:10px" pl-2 pb-2 pr-2 xs2>
                  <v-layout row align-center justify-space-between>
                    <v-icon style="color:red" @click="editUsergroup(vs)">edit</v-icon>
                    <v-icon style="color:red" @click="deleteUsergroup(vs)">clear</v-icon>
                  </v-layout>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
          <v-divider></v-divider>
        </div>

        <v-layout row>
          <v-flex class="text-xs-center" pt-3 xs12>
            <p style="margin-bottom:2px;color: rgba(0,0,0,0.54);font-size:12px;">Menampilkan <span class="red--text">{{
              xtotalfilterusergroups }}</span> dari <span class="red--text">{{ xtotalusergroups }}</span>
              total data</p>
            <v-btn small v-if="xshowall === 'N'" flat @click="updateShowAll('Y')" color="primary" dark>Tampilkan
              Semua</v-btn>
            <v-btn small v-if="xshowall === 'Y'" flat @click="updateShowAll('N')" color="primary" dark>Batasi
              data</v-btn>
          </v-flex>
        </v-layout>

        <template>
          <v-layout row justify-center>
            <v-dialog v-model="dialogusergroup" persistent max-width="600px">
              <v-card>
                <v-card-title>
                  <span class="headline">Form User Group</span>
                </v-card-title>
                <v-card-text class="pt-0 pb-0">
                  <v-form ref="formusergroup" v-model="valid" lazy-validation>
                    <v-layout wrap>
                      <v-flex xs12>
                        <v-text-field v-model="usergroupname" label="Nama User Group" :rules="nameRules"
                          required></v-text-field>
                      </v-flex>
                      <!--<v-flex xs12>
                          <v-text-field v-model="usergroupdashboard" label="Nama Dashboard" :rules="dashboardRules" required></v-text-field>
                        </v-flex>-->
                      <v-flex xs12>
                        <v-select item-text="name" return-object :items="xdashboards" v-model="xselected_dashboard"
                          label="Dashboard"></v-select>
                      </v-flex>
                      <v-flex xs12>
                        <v-checkbox true-value="Y" false-value="N" v-model="usergroupclinic" label="Is Clinic"
                          :rules="clinicRules" required></v-checkbox>
                      </v-flex>
                      <v-flex>
                        <p v-for="(xerror, idx) in xerrors" class="error pl-2 pr-2" style="color:#fff">{{ xerror.msg }}
                        </p>
                      </v-flex>
                    </v-layout>
                </v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn color="blue darken-1" flat @click="updateDialogFormUsergroup()">Tutup</v-btn>
                  <v-btn v-if="xact === 'new'" color="blue darken-1" flat @click="saveFormUsergroup()">Simpan</v-btn>
                  <v-btn v-if="xact === 'edit'" color="blue darken-1" flat @click="updateFormUsergroup()">Simpan
                    Perubahan</v-btn>
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
  data: () => ({
    color: "success",
    valid: false,
    usergroupname: '',
    usergroupdashboard: '',
    usergroupclinic: 'N',
    nameRules: [
      v => !!v || 'Nama User Group harus diisi'
    ],
    dashboardRules: [
      v => !!v || 'Dashboard harus diisi'
    ],
    clinicRules: [
      v => !!v || 'Clinic harus diPilih'
    ],
    dialogdeletealert: false,
    msgalert: "",
    xid: 0,
    xsearch: ""
  }),
  mounted() {
    this.$store.dispatch("usergroup/getreportsample")

    this.$store.dispatch("usergroup/lookup", { search: this.xsearch, all: this.xshowall })
  },
  computed: {
    xact() {
      return this.$store.state.usergroup.act
    },
    xerrors() {
      return this.$store.state.usergroup.errors
    },
    xshowall() {
      return this.$store.state.usergroup.show_all
    },
    vusergroups() {
      return this.$store.state.usergroup.usergroups
    },
    xtotalusergroups() {
      return this.$store.state.usergroup.total_usergroups
    },
    xtotalfilterusergroups() {
      return this.$store.state.usergroup.total_filter_usergroups
    },
    dialogusergroup() {
      return this.$store.state.usergroup.dialog_form_usergroup
    },
    xdashboards() {
      return this.$store.state.usergroup.dashboards
    },
    xselected_dashboard: {
      get() {
        return this.$store.state.usergroup.selected_dashboard
      },
      set(val) {
        this.$store.commit("usergroup/update_selected_dashboard", val)
        if (val)
          this.usergroupdashboard = val.url
      }
    },
    snackbar: {
      get() {
        return this.$store.state.usergroup.alert_success
      },
      set(val) {
        this.$store.commit("usergroup/update_alert_success", val)
      }
    },
    msgsnackbar() {
      return this.$store.state.usergroup.msg_success
    },
    lookupstatus() {
      return this.$store.state.usergroup.lookup_usergroup
    },
    selected_group() {
      return this.$store.state.usergroup.selected_usergroup
    }

  },
  methods: {
    updateShowAll(val) {
      this.$store.commit("usergroup/update_show_all", val)
      this.$store.dispatch("usergroup/lookup", { search: this.xsearch, all: this.xshowall })
    },
    isSelected(p) {
      return p.id == this.$store.state.usergroup.selected_usergroup.id
    },
    subname(name) {
      var xname = name
      if (xname.length > 18) {
        xname = xname.substring(0, 18) + '...'
      }
      return xname
    },
    selectMe(sc) {
      this.$store.commit("usergroup/update_selected_usergroup", sc)
      this.$store.dispatch("user/lookup", {
        id: this.$store.state.usergroup.selected_usergroup.id
      })
    },
    updateDialogFormUsergroup() {
      this.$store.commit("usergroup/update_dialog_form_usergroup", false)
    },
    openFormUsergroup() {
      console.log('hallo dashboard')
      var data = { act: 'new' }
      this.$store.dispatch("usergroup/getdashboards", data)
      this.$refs.formusergroup.reset()
      this.$refs.formusergroup.resetValidation()
      this.$store.commit("usergroup/update_act", 'new')
      this.$store.commit("usergroup/update_error_code", false)
      this.$store.commit("usergroup/update_error_name", false)
      this.$store.commit("usergroup/update_dialog_form_usergroup", true)
    },
    saveFormUsergroup() {
      if (this.$refs.formusergroup.validate()) {
        this.$store.dispatch("usergroup/save", {

          name: this.usergroupname,
          dashboard: this.usergroupdashboard,
          clinic: this.usergroupclinic,

        })
      }
    },
    updateFormUsergroup() {
      if (this.$refs.formusergroup.validate()) {
        this.$store.dispatch("usergroup/update", {
          id: this.xid,
          name: this.usergroupname,
          dashboard: this.usergroupdashboard,
          clinic: this.usergroupclinic
        })

      }
    },
    updateAlert_success(val) {
      this.$store.commit("usergroup/update_alert_success", val)
    },
    editUsergroup(data) {
      var xdata = data
      xdata.act = 'edit'
      this.xid = data.id
      this.$store.dispatch("usergroup/getdashboards", xdata)
      this.usergroupname = data.name
      this.usergroupdashboard = data.dashboard
      this.usergroupclinic = data.clinic
      this.$store.commit("usergroup/update_act", 'edit')
      this.$store.commit("usergroup/update_error_name", false)
      this.$store.commit("usergroup/update_error_dashboard", false)
      this.$store.commit("usergroup/update_error_clinic", false)
      this.$store.commit("usergroup/update_dialog_form_usergroup", true)
    },
    deleteUsergroup(data) {
      this.xid = data.id
      var xdata = { id: data.id, name: data.name, users: 'xxx' }
      this.$store.commit("usergroup/update_selected_usergroup", xdata)
      this.msgalert = "Yakin, mau hapus user group " + data.name + "  ?"
      this.dialogdeletealert = true
    },
    closeDeleteAlert() {
      this.$store.dispatch("usergroup/delete", {
        usergroupid: this.$store.state.usergroup.selected_usergroup.id,
        usergroupname: this.$store.state.usergroup.selected_usergroup.name
      })
      this.dialogdeletealert = false
    },
    thr_search: _.debounce(function () {
      this.$store.dispatch("usergroup/lookup", { search: this.xsearch, all: this.xshowall })
    }, 1000)
  },
  watch: {
    xsearch(val, old) {
      if (val !== old && this.lookupstatus !== 1) {
        console.log(val)
        this.xsearch = val
        this.thr_search()
      }
    }
  }
}
</script>
