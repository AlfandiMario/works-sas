<template>
  <div>
    <v-dialog v-model="dialogFitness" width="700">
      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Hasil Generate Kategori Fitness
        </v-card-title>

        <v-card-text>
          <v-layout row wrap class="mb-3">
            <v-flex xs2 style="font-weight: bold;"> Kismpulan :</v-flex>
            <v-flex xs10 style="font-weight: bold;">
              {{ dataFitness.status.name }} ({{ dataFitness.status.name_eng }})
            </v-flex>
          </v-layout>
          <v-data-table :headers="headersRstFitness" :items="dataFitness.data" hide-actions class="elevation-1">
            <template v-slot:header="{ props }">
              <tr>
                <th v-for="header in props.headers" :key="header.text">
                  {{ header.text }}
                </th>
              </tr>
            </template>

            <template v-slot:items="props">
              <td class="text-xs-left">{{ props.item.type }}</td>
              <td class="text-xs-left">{{ props.item.Nat_TestName }}</td>
              <td class="text-xs-left">{{ props.item.Mcu_KelainanName }}</td>
              <td class="text-xs-left">
                {{ props.item.Mcu_FitnessCategoryName }} ({{
                  props.item.Mcu_FitnessCategoryEng
                }})
              </td>
            </template>
          </v-data-table>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" flat @click="dialogFitness = false">
            Tutup
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog persistent v-model="dialogDoctor" width="500">
      <!-- <template v-slot:activator="{ on }">
        <v-btn color="red lighten-2" dark v-on="on"> Click Me </v-btn>
      </template> -->

      <v-card>
        <v-card-title class="headline grey lighten-2" primary-title>
          Pilih Dokter
        </v-card-title>

        <v-card-text>
          <v-autocomplete label="Dokter" v-model="selectedDoctor" class="mt-1" :items="doctorList"
            :search-input.sync="searchDoctor" auto-select-first hide-details style="font-size: 14px;" no-filter
            item-text="doctorName" return-object :loading="loading" no-data-text="Pilih Dokter">
            <!-- :disabled="disableAutocomplete()" -->
            <template slot="item" slot-scope="{ item }">
              <v-list-tile-content>
                <v-list-tile-title v-text="item.doctorName"></v-list-tile-title>
              </v-list-tile-content>
            </template>
          </v-autocomplete>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" flat @click="closeDialogDoctor()"> Tutup </v-btn>
          <v-btn color="success" flat @click="saveDoctor()">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-layout class="fill-height" column>
      <v-card class="pa-2">
        <v-layout row wrap>
          <v-flex xs2>
            <v-card class="pa-2 blue darken-4">
              <v-img :src="selectedPatient.patientFotoThumb" height="160" contain></v-img>
            </v-card>
          </v-flex>
          <v-flex xs10>
            <v-layout row wrap>
              <v-flex xs12 class="ml-3">
                <div style="justify-content: space-between; display: flex;">
                  <span class="display-1 font-weight-bold">
                    {{ selectedPatient.labNumber }}
                  </span>

                  <v-btn flat icon color="blue" @click="reloadDetail()">
                    <v-icon>refresh</v-icon>
                  </v-btn>
                </div>
              </v-flex>
              <v-flex xs12 class="ml-3"><v-divider></v-divider></v-flex>
              <v-flex xs5 class="ml-3"><v-text-field label="NAMA PASIEN" readonly
                  v-model="selectedPatient.patientFullname">
                </v-text-field></v-flex>
              <v-flex xs5 class="ml-3"><v-text-field label="JENIS KELAMIN" readonly
                  v-model="selectedPatient.patientGender"></v-text-field></v-flex>
              <v-flex xs5 class="ml-3"><v-text-field label="UMUR" readonly
                  v-model="selectedPatient.patientAge"></v-text-field></v-flex>
              <v-flex xs5 class="ml-3"><v-text-field label="PERUSAHAAN" readonly
                  v-model="selectedPatient.corporateName"></v-text-field></v-flex>
            </v-layout>
          </v-flex>
        </v-layout>
      </v-card>
      <v-card v-if="!loading && !loadingDetail && !loadingSave" class="pa-3 mt-2 fill-height">
        <v-layout style="overflow-y: scroll; height: 61dvh; overflow-x: hidden;" class="fill-height">
          <v-layout v-if="loading || loadingSave || loadingDetail" row wrap>
            <v-flex xs12>
              <v-progress-linear :indeterminate="true"></v-progress-linear>
            </v-flex>
          </v-layout>
          <v-layout row wrap class="py-2" justify-center>
            <!-- HASIL LAB -->
            <v-flex xs12 v-if="patientDetail.lab.length > 0">
              <v-layout row wrap>
                <v-flex align-content-center>
                  <div>
                    <v-chip label color="blue" class="mb-3" text-color="white">
                      <v-icon left>label</v-icon> LAB
                    </v-chip>
                  </div>
                </v-flex>
                <v-flex align-content-center text-md-right shrink>
                  <span @click="printLab()" class="icon-medium-fill-base xs1 white--text warning icon-print"></span>
                  <span @click="save()" class="icon-medium-fill-base xs1 white--text info icon-save"></span>
                </v-flex>
              </v-layout>
              <v-divider class="mt-1"></v-divider>
              <div class="px-3 py-2 mt-3">
                <v-data-table :headers="headersLab" :items="patientDetail.lab" hide-actions class="elevation-1">
                  <template v-slot:header="{ props }">
                    <tr>
                      <th v-for="header in props.headers" :key="header.text">
                        {{ header.text }}
                      </th>
                    </tr>
                  </template>

                  <template v-slot:items="props">
                    <td class="text-xs-left" v-bind:class="{
                      'green--text': props.item.status === 'Y',
                    }">
                      {{ props.item.testName }}
                    </td>
                    <td class="text-xs-left">{{ props.item.groupName }}</td>
                    <td class="text-xs-left">
                      {{ props.item.result }} {{ props.item.unitName }}
                    </td>
                    <td class="text-xs-left pa-2">
                      <!-- {{ props.item.displayResult }} -->
                      <v-text-field hide-details v-model="props.item.displayResult"></v-text-field>
                    </td>
                    <td class="text-xs-left">
                      {{ props.item.displayUnitName }}
                    </td>
                    <td class="text-xs-left">{{ props.item.aiResult }}</td>
                    <!-- <td class="text-xs-left">{{ props.item.aiConfidence }}</td> -->
                  </template>
                </v-data-table>
                <v-btn block color="secondary" :loading="loading || loadingSave" class="mt-3 mb-3" dark @click="save()">
                  SIMPAN HASIL LAB
                </v-btn>
              </div>
            </v-flex>
            <!-- HASIL NON LAB -->
            <v-flex xs12 v-if="patientDetail.nonlab.length > 0">
              <div v-for="test in patientDetail.nonlab">
                <v-divider class="mb-1"></v-divider>
                <v-layout row wrap>
                  <v-flex align-content-center class="pa-1">
                    <div>
                      <v-chip label color="blue" class="mb-1" text-color="white">
                        <v-icon left>label</v-icon> {{ test.testName }}
                      </v-chip>
                    </div>
                  </v-flex>
                  <v-flex align-content-center text-md-right shrink>
                    <!-- templateID = template_nonlabID -->
                    <span v-if="test.resultEntryStatus === 'VAL1'"
                      @click="printNonLab(test.natTestID, test.resultEntryID)"
                      class="icon-medium-fill-base xs1 white--text warning icon-print"></span>
                    <span v-if="test.resultEntryStatus === 'VAL1'" @click="saveNonlab(test)"
                      class="icon-medium-fill-base xs1 white--text info icon-save"></span>
                  </v-flex>
                </v-layout>
                <v-divider class="mt-1"></v-divider>
                <div class="px-3 py-2 mt-3" v-if="test.resultEntryStatus === 'VAL1'">
                  <v-data-table :headers="headersNonlab" :items="test.detail" hide-actions class="elevation-1">
                    <template v-slot:header="{ props }">
                      <tr>
                        <th v-for="header in props.headers" :key="header.text">
                          {{ header.text }}
                        </th>
                      </tr>
                    </template>

                    <template v-slot:items="props">
                      <td class="text-xs-left" v-bind:class="{
                        'green--text': props.item.status === 'Y',
                      }">
                        {{ props.item.templateDetailName }}
                      </td>
                      <td class="text-xs-left">
                        {{ props.item.templateDetailLangName }}
                      </td>
                      <td class="text-xs-left">
                        {{ props.item.result }}
                      </td>
                      <td class="text-xs-left pa-2">
                        <v-textarea
                          v-if="props.item.templateDetailIsResult === 'Y' && (test.testID === '1688' || test.testID === '1675')"
                          hide-details v-model="props.item.displayResult" auto-grow rows="2"></v-textarea>
                        <v-textarea v-if="test.testID !== '1688' && test.testID !== '1675'" hide-details
                          v-model="props.item.displayResult" auto-grow rows="2"></v-textarea>
                      </td>
                      <td class="text-xs-left">{{ props.item.aiResult }}</td>
                    </template>
                  </v-data-table>
                  <v-btn v-if="test.resultEntryStatus === 'VAL1'" block color="secondary"
                    :loading="loading || loadingSave" class="mt-3 mb-3" dark @click="saveNonlab(test)">
                    SIMPAN HASIL {{ test.testName }}
                  </v-btn>
                </div>
                <div v-if="test.resultEntryStatus !== 'VAL1'" class="pa-2">
                  <p style="color: red;">
                    Hasil {{ test.testName }} belum divalidasi
                  </p>
                </div>
              </div>
            </v-flex>
            <!-- HASIL FISIK UMUM -->
            <v-flex xs12 v-if="patientDetail.fisik.length > 0">
              <div v-for="test in patientDetail.fisik">
                <div v-if="test.templateID === '27'">
                  <v-divider class="mb-1"></v-divider>
                  <v-layout row wrap>
                    <v-flex align-content-center class="pa-1">
                      <div>
                        <v-chip label color="blue" class="mb-1" text-color="white">
                          <v-icon left>label</v-icon> {{ test.testName }}
                        </v-chip>
                      </div>
                    </v-flex>
                    <v-flex align-content-center text-md-right shrink>
                      <span v-if="test.resultEntryStatus === 'VAL1'" @click="printFisik()"
                        class="icon-medium-fill-base xs1 white--text warning icon-print"></span>
                      <span v-if="test.resultEntryStatus === 'VAL1'" @click="saveFisikUmum(test)"
                        class="icon-medium-fill-base xs1 white--text info icon-save"></span></v-flex>
                  </v-layout>
                  <v-divider class="mt-1"></v-divider>
                  <div class="px-3 py-2 mt-3">
                    <v-data-table v-if="test.resultEntryStatus === 'VAL1'" :headers="headersFisikUmum"
                      :items="test.detail" hide-actions class="elevation-1">
                      <template v-slot:header="{ props }">
                        <tr>
                          <th v-for="header in props.headers" :key="header.text">
                            {{ header.text }}
                          </th>
                        </tr>
                      </template>

                      <template v-slot:items="props">
                        <td class="text-xs-left" v-bind:class="{
                          'green--text': props.item.status === 'Y',
                        }">
                          {{ props.item.title }}
                        </td>
                        <td class="text-xs-left">{{ props.item.label }}</td>
                        <td class="text-xs-left">
                          {{ props.item.value }}
                        </td>
                        <td class="text-xs-left pa-2">
                          <!-- {{ props.item.displayResult }} -->
                          <v-text-field hide-details v-model="props.item.displayResult"></v-text-field>
                        </td>
                        <!-- <td class="text-xs-left">
                          {{ props.item.unit }}
                        </td> -->
                        <td class="text-xs-left">{{ props.item.aiResult }}</td>
                        <!-- <td class="text-xs-left">
                          {{ props.item.aiConfidence }}
                        </td> -->
                      </template>
                    </v-data-table>
                    <v-btn block v-if="test.resultEntryStatus === 'VAL1'" color="secondary"
                      :loading="loading || loadingSave" class="mt-3 mb-3" dark @click="saveFisikUmum(test)">
                      SIMPAN HASIL {{ test.testName }}
                    </v-btn>
                    <div v-if="test.resultEntryStatus !== 'VAL1'" class="pa-2">
                      <p style="color: red;">
                        Hasil {{ test.testName }} belum divalidasi
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </v-flex>
          </v-layout>
        </v-layout>
      </v-card>

      <v-card v-if="(loading || loadingDetail || loadingSave)" class="pa-3 mt-2">
        <v-layout v-if="loading || loadingDetail || loadingSave" row wrap>
          <v-flex xs12>
            <v-progress-linear :indeterminate="true"></v-progress-linear>
          </v-flex>
        </v-layout>
      </v-card>
    </v-layout>
    <one-dialog-print :title="printtitle" :width="printwidth" :height="500" :status="openprint" :urlprint="urlprint"
      @close-dialog-print="closePrint"></one-dialog-print>
  </div>
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
</style>
<script>
// const { data } = require("./onePriceHeader.vue");

module.exports = {
  components: {
    "one-dialog-print": httpVueLoader("../../common/oneDialogPrintX.vue"),
  },
  data() {
    return {
      // {
      //     text: "AI CONFIDENCE",
      //     value: "aiConfidence",
      //     width: "10%",
      //     class: "pa-2 blue darken-2 white--text",
      //   },
      headersRstFitness: [
        { text: "Tipe", value: "type" },
        { text: "Nama Test", value: "Nat_TestName" },
        { text: "Nama Kelainan", value: "Nat_TestName" },
        { text: "Kategori Fitness", value: "Mcu_FitnessCategoryName" },
      ],
      headersLab: [
        {
          text: "TEST NAME",
          value: "testName",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "GROUP",
          value: "groupName",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "RESULT",
          value: "result",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "RESULT TRANSLATE",
          value: "displayResult",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "UNIT",
          value: "displayUnitName",
          width: "10%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "ENGLISH",
          value: "aiResult",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
      ],

      headersNonlab: [
        {
          text: "NAME",
          value: "templateDetailName",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "TRANSLATE NAME",
          value: "templateDetailLangName",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "RESULT",
          value: "result",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "RESULT TRANSLATE",
          value: "displayResult",
          width: "25%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "ENGLISH",
          value: "aiResult",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
        // {
        //   text: "AI CONFIDENCE",
        //   value: "aiConfidence",
        //   width: "5%",
        //   class: "pa-2 blue darken-2 white--text",
        // },
      ],
      headersFisikUmum: [
        {
          text: "TYPE",
          value: "type",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "LABEL",
          value: "label",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "RESULT",
          value: "value",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "RESULT TRANSLATE",
          value: "displayResult",
          width: "25%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "AI RESULT",
          value: "aiResult",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "AI CONFIDENCE",
          value: "aiConfidence",
          width: "5%",
          class: "pa-2 blue darken-2 white--text",
        },
      ],
      printtitle: "",
      printwidth: "80%",
      openprint: false,
      urlprint: "",
      printType: [
        {
          name: "Hasil Resume Individu",
          reportName: "resume",
        },
        {
          name: "Kesimpulan dan Saran",
          reportName: "kesimpulandansaran",
        },
        {
          name: "Kesimpulan dan Saran 2 Tahun Logo",
          reportName: "kesimpulandansaran2logo",
        },
        {
          name: "Kesimpulan dan Saran 2 Tahun",
          reportName: "kesimpulandansaran2",
        },
        {
          name: "Gabungan Logo",
          reportName: "gabunganlogo",
        },
        {
          name: "Gabungan",
          reportName: "gabungan",
        },
        {
          name: "Gabungan Logo Tanpa Kesimpulan",
          reportName: "gabunganlogotanpakesimpulan",
        },
        {
          name: "Gabungan Tanpa Kesimpulan",
          reportName: "gabungantanpakesimpulan",
        },
        {
          name: "Gabungan 2 Tahun Logo",
          reportName: "gabungan2logo",
        },
        {
          name: "Gabungan 2 Tahun",
          reportName: "gabungan2",
        },
        {
          name: "Cover",
          reportName: "cover",
        },
      ],
      selectedPrintType: {
        name: "Cover",
        reportName: "cover",
      },
      headers: [
        {
          text: "STATUS",
          align: "center",
          sortable: false,
          value: "lab",
          width: "10%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "TEST",
          align: "center",
          sortable: false,
          value: "lab",
          width: "25%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "AMOUNT",
          align: "center",
          sortable: false,
          value: "lab",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "DISKON",
          align: "center",
          sortable: false,
          value: "name",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "DISKON RP",
          align: "center",
          sortable: false,
          value: "name",
          width: "15%",
          class: "pa-2 blue darken-2 white--text",
        },
        {
          text: "TOTAL",
          align: "center",
          sortable: false,
          value: "status",
          width: "20%",
          class: "pa-2 blue darken-2 white--text",
        },
      ],
    };
  },
  mounted() { },
  methods: {
    generateFitness() {
      this.$store.dispatch("resume/generateFitnessCategory");
    },
    closePrint() {
      this.urlprint = "";
      this.openprint = false;
    },
    reloadDetail() {
      this.$store.dispatch("resume/getdetail");
    },
    printLab() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      // https://devcpone.aplikasi.web.id/birt/run?__report=report/one/lab/rpt_test_eng.rptdesign&__format=pdf&PID=1&username=PETUGAS%20SAMPLE%20LAB&tm=1731902213650
      //cover
      this.printtitle = "Print Hasil Lab";
      this.urlprint =
        "/birt/run?__report=report/one/lab/" +
        "rpt_test_eng.rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PID=" +
        this.selectedPatient.orderID +
        "&tm=" +
        n;
      console.log(this.urlprint);
      this.openprint = true;
    },
    printNonLab(natTestID, resultEntryID) {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();

      // Ensure NatTestID is a number and base 10 (decimal)
      natTestID = parseInt(natTestID, 10);
      this.urlprint = "";

      switch (natTestID) {
        case 5283: // Treadmill
          this.printtitle = "Print Hasil Treadmill";
          this.urlprint = "/birt/run?__report=report/one/lab/rpt_hasil_so_treadmill.rptdesign&__format=pdf&username=" + user.M_StaffName + "&PID=" + resultEntryID + "&tm=" + n;
          break;
        case 5308: // Audiometri
          this.printtitle = "Print Hasil Audiometri";
          this.urlprint = "/birt/run?__report=report/one/lab/rpt_hasil_so_audiometri.rptdesign&__format=pdf&username=" + user.M_StaffName + "&PID=" + resultEntryID + "&tm=" + n;
          break;
        case 5798: // Thorax
          this.printtitle = "Print Hasil Thorax";
          this.urlprint = "/birt/run?__report=report/one/lab/rpt_hasil_so_xray.rptdesign&__format=pdf&username=" + user.M_StaffName + "&PID=" + resultEntryID + "&tm=" + n;
          break;
        case 5321: // Spirometri
          this.printtitle = "Print Hasil Spirometri";
          this.urlprint = "/birt/run?__report=report/one/lab/rpt_hasil_so_spirometri.rptdesign&__format=pdf&username=" + user.M_StaffName + "&PID=" + resultEntryID + "&tm=" + n;
          break;
        case 5280: // ECG
          this.printtitle = "Print Hasil ECG";
          this.urlprint = "/birt/run?__report=report/one/lab/rpt_hasil_so_ecg.rptdesign&__format=pdf&username=" + user.M_StaffName + "&PID=" + resultEntryID + "&tm=" + n;
          break;
        default:
          this.printtitle = "Print Hasil Non Lab";
          this.urlprint = "#";
          break;
      }
      console.log("URL Print: " + this.urlprint);
      this.openprint = true;
    },

    printTipeFunction() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();

      //cover
      this.printtitle = this.selectedPrintType.name;
      if (this.selectedPrintType.reportName === "cover") {
        let rptname = "rpt_mcu_resume_cover";
        this.urlprint =
          "/birt/run?__report=report/one/mcu/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          this.patientDetail.resumeID +
          "&tm=" +
          n;
      } else if (this.selectedPrintType.reportName === "kesimpulandansaran") {
        let rptname = "rpt_mcu_saran_kesimpulan_v2";
        this.urlprint =
          "/birt/run?__report=report/one/mcu/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          this.patientDetail.resumeID +
          "&tm=" +
          n;
      } else if (
        this.selectedPrintType.reportName === "kesimpulandansaran2logo"
      ) {
        // https://devcpone.aplikasi.web.id/birt/frameset?__report=report/one/mcu/rpt_mcu_saran_kesimpulan_portal.rptdesign&__format=pdf&username=admin&PID=6&tm=240805134257
        let rptname = "rpt_mcu_saran_kesimpulan_portal";
        this.urlprint =
          "/birt/run?__report=report/one/mcu/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          this.patientDetail.resumeID +
          "&tm=" +
          n;
      } else if (
        this.selectedPrintType.reportName === "kesimpulandansaran2"
      ) {
        let rptname = "rpt_mcu_saran_kesimpulan";
        this.urlprint =
          "/birt/run?__report=report/one/mcu/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          this.patientDetail.resumeID +
          "&tm=" +
          n;
      } else if (this.selectedPrintType.reportName === "resume") {
        let rptname = "rpt_mcu_resume";
        this.urlprint =
          "/birt/run?__report=report/one/mcu/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          this.patientDetail.resumeID +
          "&tm=" +
          n;
      } else if (this.selectedPrintType.reportName === "gabunganlogo") {
        // https://cpone.aplikasi.web.id/one-api/tools/listrptpatienttahunanportal/get_list_patient_rpt/322
        this.urlprint =
          "/one-api/tools/listrptpatienttahunanportal/get_list_patient_rpt/" +
          this.selectedPatient.orderID +
          "/" +
          n;
      } else if (this.selectedPrintType.reportName === "gabungan") {
        // https://cpone.aplikasi.web.id/one-api/tools/listrptpatienttahunan/get_list_patient_rpt/322
        this.urlprint =
          "/one-api/tools/listrptpatienttahunan/get_list_patient_rpt/" +
          this.selectedPatient.orderID +
          "/" +
          n;
      } else if (
        this.selectedPrintType.reportName === "gabunganlogotanpakesimpulan"
      ) {
        // https://cpone.aplikasi.web.id/one-api/tools/listrptpatienttahunanportal/get_list_patient_rpt/322
        this.urlprint =
          "/one-api/tools/listrptpatienttahunanportal/get_list_patient_rpt/" +
          this.selectedPatient.orderID +
          "/" +
          n +
          "/Y";
      } else if (
        this.selectedPrintType.reportName === "gabungantanpakesimpulan"
      ) {
        // https://cpone.aplikasi.web.id/one-api/tools/listrptpatienttahunan/get_list_patient_rpt/322
        this.urlprint =
          "/one-api/tools/listrptpatienttahunan/get_list_patient_rpt/" +
          this.selectedPatient.orderID +
          "/" +
          n +
          "/Y";
      } else if (this.selectedPrintType.reportName === "gabungan2logo") {
        this.urlprint =
          "/one-api/tools/listrptpatientportal/get_list_patient_rpt/" +
          this.selectedPatient.orderID +
          "/" +
          n;
      } else if (this.selectedPrintType.reportName === "gabungan2") {
        this.urlprint =
          "/one-api/tools/listrptpatient/get_list_patient_rpt/" +
          this.selectedPatient.orderID +
          "/" +
          n;
      }
      this.openprint = true;
    },
    print() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      // https://devcpone.aplikasi.web.id/birt/run?__report=report/one/rekap/rpt_list_handover_001.rptdesign&__format=pdf&PID=4&username=joko@gmail.com&tm=1722401933188
      let rptname = "rpt_mcu_resume";
      this.urlprint =
        "/birt/run?__report=report/one/mcu/" +
        rptname +
        ".rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PID=" +
        this.patientDetail.resumeID +
        "&tm=" +
        n;
      this.openprint = true;
    },
    print2tahun() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      //devcpone.aplikasi.web.id/birt/run?__report=report/one/mcu/rpt_mcu_resume.rptdesign&__format=pdf&PID=1&username=adhi&tm=1717726294764
      let rptname = "rpt_mcu_saran_kesimpulan";
      this.urlprint =
        "/birt/run?__report=report/one/mcu/" +
        rptname +
        ".rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PID=" +
        this.patientDetail.resumeID +
        "&tm=" +
        n;
      this.openprint = true;
    },
    printkesimpulansaran() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      //devcpone.aplikasi.web.id/birt/run?__report=report/one/mcu/rpt_mcu_saran_kesimpulan_v2.rptdesign&__format=pdf&PID=1&username=adhi&tm=1717726294764
      let rptname = "rpt_mcu_saran_kesimpulan_v2";
      this.urlprint =
        "/birt/run?__report=report/one/mcu/" +
        rptname +
        ".rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PID=" +
        this.patientDetail.resumeID +
        "&tm=" +
        n;
      this.openprint = true;
    },
    printCover() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      //devcpone.aplikasi.web.id/birt/run?__report=report/one/mcu/rpt_mcu_resume.rptdesign&__format=pdf&PID=1&username=adhi&tm=1717726294764
      let rptname = "rpt_mcu_resume_cover";
      this.urlprint =
        "/birt/run?__report=report/one/mcu/" +
        rptname +
        ".rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PID=" +
        this.patientDetail.resumeID +
        "&tm=" +
        n;
      this.openprint = true;
    },
    printGabungan() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      //https://cpone.aplikasi.web.id/one-api/tools/listrptpatient/get_list_patient_rpt/322sss
      let rptname = "rpt_mcu_resume_cover";

      this.urlprint =
        "/one-api/tools/listrptpatient/get_list_patient_rpt/" +
        this.selectedPatient.orderID;
      this.openprint = true;
    },
    printGabunganKop() {
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      // https://cpone.aplikasi.web.id/one-api/tools/listrptpatientportal/get_list_patient_rpt/322
      //https://cpone.aplikasi.web.id/one-api/tools/listrptpatient/get_list_patient_rpt/322sss
      let rptname = "rpt_mcu_resume_cover";
      this.urlprint =
        "/one-api/tools/listrptpatientportal/get_list_patient_rpt/" +
        this.selectedPatient.orderID;
      this.openprint = true;
    },
    saveDoctor() {
      //   let dctr = {};
      //   this.searchDoctor = "";
      //   this.selectedDoctor = dctr;
      //   this.dialogDoctor = false;

      this.$store.dispatch("resume/savedoctor", {
        id: this.patientDetail.orderID,
        doctorid: this.selectedDoctor.doctorID,
      });
    },
    getTitle(title) {
      let rtn = false;
      for (let i = 0; i < this.patientDetail.detail.length; i++) {
        if (
          this.patientDetail.detail[i]["category"].toLowerCase() ===
          title.toLowerCase()
        ) {
          rtn = true;
        }
      }
      return rtn;
    },
    disableForm() {
      let disable = false;
      if (
        this.patientDetail.status.toLowerCase() !== "new" ||
        this.loading ||
        this.loadingDetail ||
        this.loadingSave
      ) {
        disable = true;
      }
      return disable;
    },
    save() {
      if (this.loading || this.loadingSave || this.loadingDetail) return;

      let prm = this.patientDetail;
      prm.lang = 2;
      console.log(prm);
      // return;
      this.$store.dispatch("resume/save", prm);
    },
    saveNonlab(test) {
      if (this.loading || this.loadingSave || this.loadingDetail) return;

      let prm = {};
      prm.nonlab = test;
      prm.lang = 2;
      // return;
      console.log(test);
      // return;
      this.$store.dispatch("resume/saveNonlab", prm);
    },
    saveFisikUmum(test) {
      if (this.loading || this.loadingSave || this.loadingDetail) return;

      let prm = {};
      prm.fisik = test;
      prm.lang = 2;
      // return;
      console.log(test);
      // return;
      this.$store.dispatch("resume/saveFisikUmum", prm);
    },
    validate() {
      if (this.loading || this.loadingSave || this.loadingDetail) return;
      if (
        this.patientDetail.resumeDoctorID === "0" ||
        this.patientDetail.resumeDoctorID === 0 ||
        this.patientDetail.resumeDoctorID === "" ||
        this.patientDetail.resumeDoctorID === null
      ) {
        alert("Pilih dokter terlebih dahulu");
        return;
      }

      let cekLab = false;
      this.patientDetail.detail.forEach((e) => {
        if (
          e.category == "LAB" &&
          e.generateKelainan.includes("belum ada hasil")
        ) {
          cekLab = true;
        }
      });
      let text = "Apakah anda yakin validasi resume individu ini ?";
      if (cekLab) {
        text =
          "Ada pemeriksaan lab yang belum memiliki hasil, apakah anda yakin validasi data ?\n\n*untuk generate ulang hasil lab saat hasil sudah ada silahkan kosongkan inputnya";
      }
      if (!confirm(text)) {
        return;
      }
      let data = this.selectedPatient;

      data.header = this.patientDetail;
      data.detail = this.patientDetail.detail;
      data.kesimpulan = this.kesimpulan;
      data.rekomendasi = this.rekomendasi;
      data.saran = this.saran;
      data.fitnessCategory = this.selectedFitnessCategory;
      data.act = "VAL";
      let prm = {
        data: data,
      };
      console.log(prm);
      this.$store.dispatch("resume/save", prm);
    },
    unvalidate() {
      if (this.loading || this.loadingSave || this.loadingDetail) return;

      let data = this.selectedPatient;
      data.header = this.patientDetail;
      data.detail = this.patientDetail.detail;
      data.kesimpulan = this.kesimpulan;
      data.rekomendasi = this.rekomendasi;
      data.saran = this.saran;
      data.act = "UNVAL";
      let prm = {
        data: data,
      };
      console.log(prm);
      this.$store.dispatch("resume/save", prm);
    },
    getDoctorName(name) {
      console.log(name);
      console.log("Name");
      let nm = name;
      if (name === null || name === "") {
        nm = "Pilih Dokter";
      }
      return nm;
    },
    openDialogDoctor(val) {
      if (this.loading || this.loadingSave || this.loadingDetail) return;
      if (val["status"] !== "NEW") return;
      console.log(val);
      this.selectedDoctrorTest = val;
      let dctr = {
        M_DoctorID: val.doctorID,
        doctorID: val.doctordoctorIDName,
        M_DoctorPrefix: "",
        M_DoctorPrefix2: "",
        M_DoctorName: "",
        M_DoctorSuffix: "",
        M_DoctorSuffix2: "",
        M_DoctorCode: "",
        doctorName: val.doctorName,
      };
      this.searchDoctor = val.doctorName;
      this.selectedDoctor = dctr;
      this.dialogDoctor = true;
    },
    closeDialogDoctor() {
      this.selectedDoctrorTest = {};
      let dctr = {};
      this.searchDoctor = "";
      this.selectedDoctor = dctr;
      this.dialogDoctor = false;
    },
    resetInput() {
      this.$store.commit("resume/reset_input");
    },
    handleChangeInput(data) {
      // console.log(data);
      let pd = this.patientDetail;
      pd.detail.forEach((e) => {
        if (e.detailOrderID === data.detailOrderID) {
          e = data;
        }
      });
      this.patientDetail = pd;
    },
    handleChangeInputHeader(data) {
      // console.log(data);
      let pd = this.patientDetail;
      pd.kesimpulan = data.kesimpulan;
      pd.rekomendasi = data.rekomendasi;
      pd.saran = data.saran;
      this.patientDetail = pd;
    },
    thr_search_doctor: _.debounce(function () {
      prm = {
        search: this.searchDoctor,
      };
      this.$store.dispatch("resume/getdoctorlist", prm);
    }, 100),
  },
  computed: {
    dataFitness: {
      get() {
        return this.$store.state.resume.dataFitness;
      },
      set(val) {
        this.$store.commit("resume/update_dataFitness", val);
      },
    },
    dialogFitness: {
      get() {
        return this.$store.state.resume.dialogFitness;
      },
      set(val) {
        this.$store.commit("resume/update_dialogFitness", val);
      },
    },
    selectedFitnessCategory: {
      get() {
        return this.$store.state.resume.selectedFitnessCategory;
      },
      set(val) {
        this.$store.commit("resume/update_selectedFitnessCategory", val);
      },
    },
    fitnessCategory: {
      get() {
        return this.$store.state.resume.fitnessCategory;
      },
      set(val) {
        this.$store.commit("resume/update_fitnessCategory", val);
      },
    },
    selectedPatient: {
      get() {
        return this.$store.state.resume.selectedPatient;
      },
      set(val) {
        this.$store.commit("resume/update_selectedPatient", val);
      },
    },
    loading: {
      get() {
        return this.$store.state.resume.loading;
      },
      set(val) {
        this.$store.commit("resume/update_loading", val);
      },
    },
    loadingSave: {
      get() {
        return this.$store.state.resume.loadingSave;
      },
      set(val) {
        this.$store.commit("resume/update_loadingSave", val);
      },
    },
    loadingDetail: {
      get() {
        return this.$store.state.resume.loadingDetail;
      },
      set(val) {
        this.$store.commit("resume/update_loadingDetail", val);
      },
    },
    patientDetail: {
      get() {
        return this.$store.state.resume.patientDetail;
      },
      set(val) {
        this.$store.commit("resume/update_patientDetail", val);
      },
    },
    // rekomendasi: "",
    // saran: "",
    // kesimpulan: "",
    rekomendasi: {
      get() {
        return this.$store.state.resume.rekomendasi;
      },
      set(val) {
        this.$store.commit("resume/update_rekomendasi", val);
      },
    },
    saran: {
      get() {
        return this.$store.state.resume.saran;
      },
      set(val) {
        this.$store.commit("resume/update_saran", val);
      },
    },
    kesimpulan: {
      get() {
        return this.$store.state.resume.kesimpulan;
      },
      set(val) {
        this.$store.commit("resume/update_kesimpulan", val);
      },
    },
    doctorList() {
      return this.$store.state.resume.doctorList;
    },
    selectedDoctor: {
      get() {
        return this.$store.state.resume.selectedDoctor;
      },
      set(val) {
        this.$store.commit("resume/update_selectedDoctor", val);
      },
    },
    searchDoctor: {
      get() {
        return this.$store.state.resume.searchDoctor;
      },
      set(val) {
        this.$store.commit("resume/update_searchDoctor", val);
      },
    },
    dialogDoctor: {
      get() {
        return this.$store.state.resume.dialogDoctor;
      },
      set(val) {
        this.$store.commit("resume/update_dialogDoctor", val);
      },
    },
  },
  watch: {
    searchDoctor(val, old) {
      console.log(val);
      if (val == old) return;
      if (!val) return;
      if (val.length < 1) return;
      if (this.selectedDoctor.M_DoctorName == val) return;
      this.thr_search_doctor();
    },
  },
};
</script>
