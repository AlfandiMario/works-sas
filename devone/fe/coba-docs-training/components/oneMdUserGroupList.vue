<!-- oneMdUserGroupList.vue -->
<template>
  <v-layout>
    <v-flex xs12>
      <v-card class="scroll-container">
        <!-- Toolbar -->
        <v-toolbar color="blue lighten-3" dark height="50px">
          <v-toolbar-title>USER GROUP</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="openFormUsergroup()">
            <v-icon>library_add</v-icon>
          </v-btn>
        </v-toolbar>

        <!-- Search Bar -->
        <v-layout row style="background:#bbdefb;padding-top:5px;" justify-left>
          <v-list-tile>
            <input type="text" v-model="xsearch" class="textinput" placeholder="Cari ..." />
          </v-list-tile>
        </v-layout>

        <!-- User Group List -->
        <div v-for="(usergroup, index) in vusergroups" :key="index">
          <v-layout pa-2 row>
            <v-flex xs12>
              <v-layout row>
                <v-flex class="boxsolid" xs10>
                  {{ usergroup.name }}
                </v-flex>
                <v-flex class="boxsolid text-center" style="padding-top:10px" pl-2 pb-2 pr-2 xs2>
                  <v-layout row align-center justify-space-between>
                    <v-icon style="color: #b3b3b3;" @click="editUsergroup(usergroup)">edit</v-icon>
                    <v-icon style="color:#b3b3b3" @click="deleteUsergroup(usergroup)">clear</v-icon>
                  </v-layout>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
          <v-divider></v-divider>
        </div>

        <!-- Dialog Form -->
        <v-dialog v-model="dialogusergroup" persistent max-width="600px">
          <v-card>
            <v-card-title>
              <span class="headline">Form User Group</span>
            </v-card-title>
            <v-card-text>
              <v-form ref="formusergroup" v-model="valid" lazy-validation>
                <v-text-field v-model="usergroupname" label="Nama User Group" required></v-text-field>
                <v-btn flat @click="updateDialogFormUsergroup()">Tutup</v-btn>
                <v-btn flat @click="saveFormUsergroup()">Simpan</v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </v-dialog>

        <!-- Content -->
        <div>
          <!-- Tempatkan elemen lain di sini -->
        </div>
      </v-card>
    </v-flex>
  </v-layout>
</template>

<script>
module.exports = {
  data() {
    return {
      xsearch: "", // Model untuk pencarian
      xshowall: 'N', // Model untuk menampilkan semua data
    };
  },
  mounted() {
    this.$store.dispatch("usergroup/lookup", { search: this.xsearch, all: this.xshowall })
  },
  computed: {
    valid() {
      return this.$store.state.usergroup.valid;
    },
    vusergroups() {
      return this.$store.state.usergroup.usergroups;
    },
    dialogusergroup: {
      get() {
        return this.$store.state.usergroup.dialogusergroup;
      },
      set(value) {
        this.$store.commit('usergroup/update_dialogusergroup', value);
      },
    },
    usergroupname: {
      get() {
        return this.$store.state.usergroup.usergroupname;
      },
      set(value) {
        this.$store.commit('usergroup/update_usergroupname', value);
      },
    },
  },
  methods: {
    openFormUsergroup() {
      this.dialogusergroup = true;
    },
    updateDialogFormUsergroup() {
      this.dialogusergroup = false;
    },
    saveFormUsergroup() {
      console.log("Simpan User Group:", this.usergroupname);
      this.dialogusergroup = false;
    },
    editUsergroup(usergroup) {
      this.openFormUsergroup();
      console.log("Edit User Group:", usergroup);
    },
    deleteUsergroup(usergroup) {
      console.log("Hapus User Group:", usergroup);
    },
  },
};
</script>