<template>
  <div>
    <v-dialog v-model="dialogdownload" persistent max-width="650" max-height="500">
      <v-card>
        <v-container grid-list-sm fluid>
          <v-layout row wrap>
            <v-flex xs12>
              <p class="mb-2">PILIH YANG DIINGINKAN :</p>
              <v-layout class="pl-2 pt-2" row>
                <v-flex xs12>
                  <v-radio-group v-model="selected_download">
                    <v-radio v-for="(report, i) in reports" :key="i" :label="report.test" :value="report.url"></v-radio>
                  </v-radio-group>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
        </v-container>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error darken-1" flat @click="dialogdownload = false">Tutup</v-btn>
          <v-btn color="teal lighten-2" flat @click="doDownloadRpt()">DOWNLOAD</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialognote" width="75%">
      <v-card>
        <v-card-title class="headline white--text error" primary-title>
        </v-card-title>

        <v-card-text>
          <v-layout v-if="patient.fo_note !== ''" mb-2 row>
            <v-flex mb-2 xs3>
              <span style="color: #0f80db;" class="mono name">FO</span>
            </v-flex>
            <v-flex xs9>
              <v-layout row>
                <v-flex mb-1 xs12>
                  <code style="
                      box-shadow: none !important;
                      color: #0f80db !important;
                      background-color: #a8cfee6b !important;
                    ">{{ patient.fo_note_user }}</code>
                  <div class="v-markdown">
                    <p style="margin-top: 2px; margin-bottom: 0;">
                      {{ patient.fo_note }}
                    </p>
                  </div>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
          <v-layout v-if="patient.fo_ver_note !== ''" mb-2 row>
            <v-flex mb-2 xs3>
              <span style="color: #0f80db;" class="mono name">FO Verifikasi</span>
            </v-flex>
            <v-flex xs9>
              <v-layout row>
                <v-flex mb-1 xs12>
                  <code style="
                      box-shadow: none !important;
                      color: #0f80db !important;
                      background-color: #a8cfee6b !important;
                    ">{{ patient.fo_ver_note_user }}</code>
                  <div class="v-markdown">
                    <p style="margin-top: 2px; margin-bottom: 0;">
                      {{ patient.fo_ver_note }}
                    </p>
                  </div>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
          <v-layout v-if="patient.sampling_note !== ''" mb-2 row>
            <v-flex mb-2 xs3>
              <span style="color: #0f80db;" class="mono name">Sampling</span>
            </v-flex>
            <v-flex xs9>
              <v-layout row>
                <v-flex mb-1 xs12>
                  <code style="
                      box-shadow: none !important;
                      color: #0f80db !important;
                      background-color: #a8cfee6b !important;
                    ">{{ patient.sampling_note_user }}</code>
                  <div class="v-markdown">
                    <p style="margin-top: 2px; margin-bottom: 0;">
                      {{ patient.sampling_note }}
                    </p>
                  </div>
                </v-flex>
              </v-layout>
            </v-flex>
          </v-layout>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" dark flat text @click="dialognote = false">
            Tutup
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogprintlang" persistent max-width="450">
      <v-card class="align-center justify-center">
        <v-card-title style="padding: 0px;" primary-title dark color="blue-grey" class="blue-grey white--text">
          <v-subheader style="color: #fff !important;">PILIH BAHASA</v-subheader>
        </v-card-title>
        <v-card-text>
          <v-switch style="margin-top: 0; padding-top: 0;" @change="changeSwitch(lang.chex, index)"
            v-for="(lang, index) in dialoglangs" :key="index" v-model="lang.chex === 'Y'" :label="lang.name"
            :disabled="lang.chex === 'N'">
          </v-switch>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey ligthen-1" flat @click="dialogprintlang = false">Batal</v-btn>
          <v-btn color="blue-grey" flat @click="doPrintAfterLang()">Cetak</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialoglang" persistent max-width="450">
      <v-card class="align-center justify-center">
        <v-card-title style="padding: 0px;" primary-title dark color="blue-grey" class="blue-grey white--text">
          <v-subheader style="color: #fff !important;">PILIH BAHASA</v-subheader>
        </v-card-title>
        <v-card-text>
          <v-select class="ma-1 mini-select" :items="xnowtest.langs" item-text="name" return-object v-model="sellang"
            label="Bahasa" outline hide-details>
          </v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey ligthen-1" flat @click="changeLang()">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialogtemplates" persistent max-width="450">
      <v-card class="align-center justify-center">
        <v-card-title style="padding: 0px;" primary-title dark color="primary" class="primary white--text">
          <v-subheader style="color: #fff !important;">PILIH TEMPLATE</v-subheader>
        </v-card-title>
        <v-card-text>
          <v-select class="ma-1 mini-select" :items="xtemplates" item-text="label" return-object
            v-model="xselectedtemplate" label="Template" outline hide-details>
          </v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey ligthen-1" flat @click="dialogtemplates = false">Batal</v-btn>
          <v-btn color="blue-grey" flat @click="doPasteTemplate()">Terapkan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="xdialogdoctor" persistent max-width="350">
      <v-card class="align-center justify-center">
        <v-card-title primary-title dark color="warning" class="warning white--text headline">PILIH
          DOKTER</v-card-title>
        <v-card-text>
          <p v-if="requiredoctor" class="error pl-2 pr-2" style="color: #fff;">
            Dokter harus dipilih salah satu dong
          </p>
          <v-autocomplete label="Dokter" v-model="selected_doctor" :items="doctors" :search-input.sync="search_doctor"
            auto-select-first no-filter item-text="name" return-object style="height: 32px !important;"
            no-data-text="Pilih Dokter">
            <template slot="item" slot-scope="{ item }">
              <v-list-tile-content>
                <v-list-tile-title v-text="item.name"></v-list-tile-title>
              </v-list-tile-content>
            </template>
          </v-autocomplete>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="red darken-1" flat @click="xdialogdoctor = false">Tutup</v-btn>
          <v-btn color="green darken-1" @click="savedoctor" flat>Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="xdialogaction" persistent max-width="350">
      <v-card>
        <v-card-title color="warning" class="headline">Konfirmasi</v-card-title>
        <v-card-text v-html="xmsgaction"> </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary darken-1" flat @click="closeDialogAction()">Ya</v-btn>
          <v-btn color="error darken-1" flat @click="xdialogaction = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="xdialogimage" persistent max-width="650" max-height="500">
      <v-card color="amber lighten-5">
        <v-container grid-list-sm fluid>
          <v-layout row wrap>
            <v-flex>
              <v-card flat tile class="d-flex">
                <v-img :src="ximage" aspect-ratio="1" class="grey lighten-2">
                  <template v-slot:placeholder>
                    <v-layout fill-height align-center justify-center ma-0>
                      <v-progress-circular indeterminate color="grey lighten-5"></v-progress-circular>
                    </v-layout>
                  </template>
                </v-img>
              </v-card>
            </v-flex>
          </v-layout>
        </v-container>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="black" dark @click="xdialogimage = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-layout row mb-2 wrap>
      <v-flex xs12 pr-1>
        <v-card>
          <v-layout pt-1 pb-1 row>
            <v-flex pt-1 pb-1 pl-2 pr-2 xs12>
              <span class="left" style="font-size: x-large;">
                <p class="mb-0">
                  {{ patient.ordernumber }}
                  <span><v-icon title="catatan" @click="dialognote = true" v-if="
                    (patient.fo_note && patient.fo_note != '') ||
                    (patient.sampling_note && patient.sampling_note != '')
                  " style="font-size: 20px; cursor: pointer;" large color="error">info</v-icon></span>
                </p>
              </span>
              <span class="right">
                <v-chip dark v-for="(delivery, ix) in patient.deliveries" :key="ix" @click="downloadRpt(delivery)"
                  :title="delivery.destination" label :color="delivery.color" text-color="white">
                  {{ delivery.label }}
                </v-chip>
              </span>
            </v-flex>
          </v-layout>
          <v-divider></v-divider>
          <v-layout pt-1 pb-2 wrap>
            <v-flex pt-1 pb-1 pl-2 sm6 xs12>
              <v-text-field ma-1 label="NAMA PASIEN" class="text-uppercase" :value="patient.patient_fullname"
                hide-details readonly></v-text-field>
            </v-flex>
            <v-flex pt-1 pb-1 pl-2 xs4 sm2>
              <v-text-field ma-1 label="JENIS KELAMIN" class="text-uppercase" :value="patient.sexname" hide-details
                readonly></v-text-field>
            </v-flex>
            <v-flex pt-1 pb-1 pl-2 sm4 xs8>
              <v-text-field ma-1 label="UMUR" class="text-uppercase" :value="patient.umur" :title="patient.dob"
                hide-details readonly></v-text-field>
            </v-flex>
          </v-layout>
        </v-card>
      </v-flex>
    </v-layout>
    <v-layout mb-2 row v-for="(test, index) in xtests" :key="index" wrap>
      <v-flex xs12 pr-1>
        <v-card>
          <v-layout row>
            <v-flex xs12>
              <v-subheader red--text text--lighten-1>
                <v-chip class="hidden-xs-only" v-if="test.status === 'NEW'" label color="grey" text-color="white">
                  <v-icon left>label</v-icon>{{ test.test_name }}
                </v-chip>
                <v-chip class="hidden-xs-only" v-if="test.status === 'VAL1'" label color="warning" text-color="white">
                  <v-icon left>label</v-icon>{{ test.test_name }}
                </v-chip>
                <v-chip class="hidden-xs-only" v-if="test.status === 'VAL2'" label color="success" text-color="white">
                  <v-icon left>label</v-icon>{{ test.test_name }}
                </v-chip>
                <v-chip class="hidden-xs-only" v-if="test.status_name === 'NO TEMPLATE'" label color="black"
                  text-color="white">
                  <v-icon left>label</v-icon>{{ test.test_name }}
                </v-chip>
                <v-chip v-if="test.doctor_fullname != ''" @click="opendialogdoctor(test)" label color="info" outline>
                  <v-icon left>assignment_ind</v-icon>{{ test.doctor_fullname }}
                </v-chip>

                <v-spacer></v-spacer>
                <span v-if="
                  check_saved(test) &&
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('Umum')
                " @click="print2tahun(test, 'print', test.template_name)"
                  class="icon-medium-fill-base-small xs1 white--text brown icon-print"></span>
                <span v-if="
                  check_saved(test) &&
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('Umum')
                " @click="print1tahun(test, 'print', test.template_name)"
                  class="icon-medium-fill-base-small xs1 white--text teal icon-print"></span>
                <span v-if="check_saved(test)" @click="print(test, 'print', test.template_name)"
                  class="icon-medium-fill-base-small xs1 white--text blue-grey icon-print"></span>
                <!--<span v-if="test.status === 'VAL1'" @click="unval1(test, 'unval1')"
                  class="hidden-xs-only icon-medium-fill-base-small xs1 white--text warning icon-unval"></span>-->
                <span v-if="test.status === 'NEW' && check_saved(test)" @click="val1(test, 'val1')"
                  class="hidden-xs-only icon-medium-fill-base-small xs1 white--text warning icon-v"></span>
                <span v-if="test.status === 'NEW'" @click="saveResult(test, 'save')"
                  class="icon-medium-fill-base-small xs1 white--text info icon-save"></span>
              </v-subheader>
              <v-divider></v-divider>
              <div>
                <v-layout v-if="test.status_name === 'NO TEMPLATE'" row wrap>
                  <v-flex pa-3 xs12>
                    <h4 class="text-align-center font-weight-black title">
                      TEMPLATE BELUM PERNAH TERCIPTA
                    </h4>
                    <blockquote class="blockquote subheading font-weight-light font-italic">
                      Silahkan setting template pemeriksaan ini terlebih dahulu
                      <span class="font-weight-bold">Berikan aku template</span>, agar ku bisa mengisi.
                    </blockquote>
                    <p class="mt-2 pb-1 pr-5 caption warning--text">
                      *) silahkan hubungi admin untuk setting template
                    </p>
                  </v-flex>
                </v-layout>
                <v-layout v-if="test.template_flag_other === 'N'" row wrap>
                  <v-flex xs12 pa-2 mb-1 pt-1>
                    <v-layout align-center justify-center v-for="(result, idxresult) in test.details" :key="idxresult"
                      row>
                      <!-- <v-flex pl-2 class="text-xs-center" xs1>
                        <v-switch
                          v-model="result.flag_print === 'Y'"
                          @change="changeFlagPrint(result.flag_print, idxresult, index)"
                        ></v-switch>
                      </v-flex>-->
                      <v-flex xs12 pa-1>
                        <v-textarea filled outline hide-details :label="result.result_label" @change="changeResult()"
                          :disabled="test.status !== 'NEW'" v-model="result.result_value" rows="3"></v-textarea>
                      </v-flex>
                    </v-layout>
                  </v-flex>
                </v-layout>
                <v-divider v-if="test.template_flag_other === 'N'"></v-divider>
                <!--<v-layout v-if="test.template_flag_other === 'N' " row wrap>
                  <v-flex xs12 pa-2 mb-1 pt-1>
                    <v-layout align-center justify-center row>
                      <v-flex xs12 pa-1>
                        <v-textarea
                          filled
                          outline
                          hide-details
                          label="Catatan"
                          :disabled="test.status !== 'NEW'"
                          v-model="test.note"
                          auto-grow
                          rows="2"
                        ></v-textarea>
                      </v-flex>
                    </v-layout>
                  </v-flex>
                </v-layout>-->
                <v-layout v-if="
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('Umum')
                " row wrap>
                  <v-flex xs12>
                    <v-card flat>
                      <v-stepper style="background: #2196f3;" v-model="tab">
                        <v-stepper-header>
                          <template v-for="n in tabs_fisik">
                            <v-stepper-step color="#ff5252" :key="`${n.id}-step`" :step="n.id" editable>
                              <span style="color: white;">{{
                                getnamelabel(n.name)
                              }}</span>
                            </v-stepper-step>
                            <v-divider class="x_step" style="border-color: #fff;" v-if="n.id !== tabs_fisik.length"
                              :key="n.id"></v-divider>
                          </template>
                        </v-stepper-header>
                        <v-stepper-items>
                          <v-stepper-content class="x_form" step="1">
                            <div v-for="(riwayat, idx_r) in riwayats" :key="idx_r">
                              <v-card class="mb-2">
                                <v-layout pb-2 row>
                                  <v-flex xs12>
                                    <v-subheader red--text text--lighten-1>
                                      <v-btn class="hidden-xs-only" v-if="riwayat.show_all === 'N'"
                                        @click="toggleDetailRiwayat(idx_r)" style="min-width: 30px;" small flat
                                        color="#fa8072"><v-icon small>add</v-icon></v-btn>
                                      <v-btn class="hidden-xs-only" v-if="riwayat.show_all === 'Y'"
                                        @click="toggleDetailRiwayat(idx_r)" style="min-width: 30px;" small flat
                                        color="#fa8072"><v-icon small>remove</v-icon></v-btn>
                                      {{ getnamelabel(riwayat.title) }}

                                      <v-flex text-md-right>
                                        <v-btn v-if="riwayat.flag_normal === 'N'" :disabled="cantedit"
                                          @click="changeFlagNormal(idx_r)" small color="error"><v-icon class="pr-1"
                                            small>close</v-icon>
                                          {{
                                            getnamelabel(
                                              riwayat.label_flag_normal
                                            )
                                          }}</v-btn>
                                        <v-btn v-if="riwayat.flag_normal === 'Y'" :disabled="cantedit"
                                          @click="changeFlagNormal(idx_r)" small color="info"><v-icon class="pr-1"
                                            small>check</v-icon>
                                          {{
                                            getnamelabel(
                                              riwayat.label_flag_normal
                                            )
                                          }}</v-btn>
                                      </v-flex>
                                    </v-subheader>
                                    <v-divider class="mb-2"></v-divider>
                                    <div v-if="riwayat.type_form === 'X'">
                                      <v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{
                                        riwayat.subtitle }}</v-layout>
                                      <v-layout pl-2 pb-1 pt-1 v-for="(r_detail, idx_detail) in generate_rows(
                                        riwayat.details
                                      )" :key="idx_detail" v-if="riwayat.show_all === 'Y'" wrap>
                                        <v-flex xs12 sm6 pa-1>
                                          <v-layout v-if="r_detail[0]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="r_detail[0].chx" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                " color="red" hide-details @change="
                                                  checkAnotherXCbx(
                                                    r_detail[0].id_code,
                                                    $event,
                                                    idx_detail,
                                                    idx_r
                                                  )
                                                  "></v-checkbox>
                                            </v-flex>
                                            <v-flex xs11>
                                              <span style="font-size: 12px;" :class="{
                                                'red--text':
                                                  r_detail[0].color &&
                                                  r_detail[0].color == 'red',
                                              }">{{
                                                getnamelabel(
                                                  r_detail[0].label
                                                )
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                        <v-flex xs12 sm6 pa-1>
                                          <v-layout v-if="r_detail[1]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="r_detail[1].chx" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                " color="red" hide-details @change="
                                                  checkAnotherXCbx(
                                                    r_detail[1].id_code,
                                                    $event,
                                                    idx_detail,
                                                    idx_r
                                                  )
                                                  "></v-checkbox>
                                            </v-flex>
                                            <v-flex xs11>
                                              <span style="font-size: 12px;" :class="{
                                                'red--text':
                                                  r_detail[1].color &&
                                                  r_detail[1].color == 'red',
                                              }">{{
                                                getnamelabel(
                                                  r_detail[1].label
                                                )
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="riwayat.type_form === 'T'">
                                      <v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{
                                        riwayat.subtitle }}</v-layout>
                                      <v-layout pl-2 pb-1 pt-1 v-for="(r_detail, idx_detail) in riwayat.details"
                                        :key="idx_detail" v-if="riwayat.show_all === 'Y'" wrap>
                                        <v-flex xs12 pa-1>
                                          <v-layout v-if="r_detail" align-center row>
                                            <v-flex xs12 pl-1 pr-2>
                                              <v-textarea outline rows="auto" :label="r_detail.label"
                                                v-model="r_detail.value"></v-textarea>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="riwayat.type_form === 'XV'">
                                      <v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{
                                        riwayat.subtitle }}</v-layout>
                                      <v-layout pl-2 pb-1 pt-1 v-for="(r_detail, idx_detail) in generate_rows(
                                        riwayat.details
                                      )" :key="idx_detail" v-if="riwayat.show_all === 'Y'" wrap>
                                        <v-flex xs12 sm6 pa-1>
                                          <v-layout v-if="r_detail[0]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="r_detail[0].chx" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                " color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;" :class="{
                                                'red--text':
                                                  r_detail[0].color &&
                                                  r_detail[0].color == 'red',
                                              }">{{
                                                getnamelabel(
                                                  r_detail[0].label
                                                )
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[0].value" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                        <v-flex xs12 sm6 pa-1>
                                          <v-layout v-if="r_detail[1]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="r_detail[1].chx" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                " color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;" :class="{
                                                'red--text':
                                                  r_detail[1].color &&
                                                  r_detail[1].color == 'red',
                                              }">{{
                                                getnamelabel(
                                                  r_detail[1].label
                                                )
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[1].value" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="riwayat.type_form === 'XVV3'">
                                      <v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{
                                        riwayat.subtitle }}</v-layout>
                                      <v-layout pl-2 pb-1 pt-1 v-for="(r_detail, idx_detail) in generate_rows(
                                        riwayat.details
                                      )" :key="idx_detail" v-if="riwayat.show_all === 'Y'" wrap>
                                        <v-flex xs12 sm6 pa-1>
                                          <v-layout v-if="r_detail[0]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="r_detail[0].chx" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                " color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;" :class="{
                                                'red--text':
                                                  r_detail[0].color &&
                                                  r_detail[0].color == 'red',
                                              }">{{
                                                getnamelabel(
                                                  r_detail[0].label
                                                )
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  margin-bottom:4px;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[0].value" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                              <v-text-field v-if="r_detail[0].value1" style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[0].value1" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                              <v-text-field v-if="r_detail[0].value2" style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[0].value2" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                        <v-flex xs12 sm6 pa-1>
                                          <v-layout v-if="r_detail[1]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="r_detail[1].chx" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                " color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;" :class="{
                                                'red--text':
                                                  r_detail[1].color &&
                                                  r_detail[1].color == 'red',
                                              }">{{
                                                getnamelabel(
                                                  r_detail[1].label
                                                )
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2>
                                              <v-layout row>
                                                <v-flex xs12>
                                                  <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  margin-bottom:5px;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[1].value" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                    hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                              <v-layout v-if="r_detail[1].hasOwnProperty('value1')" row>
                                                <v-flex xs12>
                                                  <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  margin-bottom:5px;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[1].value1" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                    hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                              <v-layout v-if="r_detail[1].hasOwnProperty('value2')" row>
                                                <v-flex xs12>
                                                  <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  margin-bottom:5px;
                                                  line-height: 25px !important;
                                                " outline v-model="r_detail[1].value2" :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " single-line :placeholder="getnamelabel('Keterangan')"
                                                    hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>


                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="riwayat.type_form === 'XVS'">
                                      <v-layout pl-2 v-for="(segment, idx_segment) in riwayat.details"
                                        :key="idx_segment" wrap>
                                        <v-flex xs12>
                                          <v-layout v-if="riwayat.show_all === 'Y'" row>{{
                                            getnamelabel(segment.name)
                                          }}</v-layout>
                                          <v-layout v-if="
                                            riwayat.show_all === 'Y' &&
                                            segment.caption &&
                                            segment.caption !== ''
                                          " row><span class="caption red--text">{{
                                            segment.caption
                                          }}</span></v-layout>
                                          <v-layout pl-2 pb-1 pt-1 v-for="(
                                              r_detail, idx_detail
                                            ) in generate_rows(segment.details)" :key="idx_detail"
                                            v-if="riwayat.show_all === 'Y'" wrap>
                                            <v-flex xs12 sm6 pa-1>
                                              <v-layout v-if="r_detail[0]" align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="r_detail[0].chx" :disabled="riwayat.flag_normal === 'Y' ||
                                                    cantedit
                                                    " color="red" hide-details @change="
                                                      checkXVSRiwayatCbx(
                                                        r_detail[0].id_code,
                                                        $event,
                                                        idx_segment,
                                                        idx_r
                                                      )
                                                      "></v-checkbox>
                                                </v-flex>
                                                <v-flex xs5>
                                                  <span style="font-size: 12px;" :class="{
                                                    'red--text':
                                                      r_detail[0].color &&
                                                      r_detail[0].color == 'red',
                                                  }">{{
                                                    getnamelabel(
                                                      r_detail[0].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex v-if="!r_detail[0]['suffix']" xs6 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="r_detail[0].value" :disabled="riwayat.flag_normal === 'Y' ||
                                                      cantedit ||
                                                      !r_detail[0].chx
                                                      " single-line :placeholder="getnamelabel('Keterangan')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                                <v-flex v-if="r_detail[0]['suffix']" xs2 pl-1>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="r_detail[0].value" :disabled="riwayat.flag_normal === 'Y' ||
                                                      cantedit ||
                                                      !r_detail[0].chx
                                                      " single-line hide-details></v-text-field>
                                                </v-flex>
                                                <v-flex v-if="r_detail[0]['suffix']" xs4 pl-1>
                                                  <span class="mono" style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      r_detail[0].suffix
                                                    )
                                                  }}</span>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs12 sm6 pa-1>
                                              <v-layout v-if="r_detail[1]" align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="r_detail[1].chx" :disabled="riwayat.flag_normal === 'Y' ||
                                                    cantedit
                                                    " color="red" hide-details @change="
                                                      checkXVSRiwayatCbx(
                                                        r_detail[1].id_code,
                                                        $event,
                                                        idx_segment,
                                                        idx_r
                                                      )
                                                      "></v-checkbox>
                                                </v-flex>
                                                <v-flex xs5>
                                                  <span style="font-size: 12px;" :class="{
                                                    'red--text':
                                                      r_detail[1].color &&
                                                      r_detail[1].color == 'red',
                                                  }">{{
                                                    getnamelabel(
                                                      r_detail[1].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs6 v-if="!r_detail[1]['suffix']" pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="r_detail[1].value" :disabled="riwayat.flag_normal === 'Y' ||
                                                      cantedit ||
                                                      !r_detail[1].chx
                                                      " single-line :placeholder="getnamelabel('Keterangan')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                                <v-flex v-if="r_detail[1]['suffix']" xs2 pl-1>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="r_detail[1].value" :disabled="riwayat.flag_normal === 'Y' ||
                                                      cantedit ||
                                                      !r_detail[1].chx
                                                      " single-line hide-details></v-text-field>
                                                </v-flex>
                                                <v-flex v-if="r_detail[1]['suffix']" xs4 pl-1>
                                                  <span class="mono" style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      r_detail[1].suffix
                                                    )
                                                  }}</span>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="riwayat.type_form === 'XO'">
                                      <v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{
                                        riwayat.subtitle }}</v-layout>
                                      <v-layout v-if="riwayat.show_all === 'Y'" class="pl-2 caption pb-2" row>
                                        <v-flex xs12 pa-1>
                                          <v-layout align-center row>
                                            <v-flex xs8 sm4></v-flex>
                                            <v-flex class="text-xs-left" xs2 sm2>
                                              Ayah
                                            </v-flex>
                                            <v-flex class="text-xs-left" xs2 sm2>
                                              Ibu
                                            </v-flex>
                                            <!----<v-flex class="text-xs-left" xs2>
                                                                                        Saudara Kandung
                                                                                    </v-flex>
                                                                                    <v-flex class="text-xs-left" xs2>
                                                                                        Kakek/Nenek
                                                                                    </v-flex>
                                                                                    <v-flex class="text-xs-left" xs2>
                                                                                        Saudara Lainnya
                                                                                    </v-flex>-->
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                      <v-layout pl-2 pb-1 pt-1 v-for="(r_detail, idx_detail) in riwayat.details"
                                        :key="idx_detail" v-if="riwayat.show_all === 'Y'" wrap>
                                        <v-flex xs12 pa-1>
                                          <v-layout align-center row>
                                            <v-flex xs8 sm4>{{
                                              getnamelabel(r_detail.label)
                                            }}</v-flex>
                                            <v-flex xs2 sm2 class="text-xs-left"
                                              v-for="(opsi, idx_opsi) in r_detail.options" :key="idx_opsi">
                                              <v-checkbox row class="mt-0 pt-0 text-xs-left" v-model="opsi.selected"
                                                :disabled="riwayat.flag_normal === 'Y' || cantedit
                                                  " color="red" hide-details></v-checkbox>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="riwayat.type_form === 'XD'">
                                      <v-layout pl-2 v-for="(segment, idx_segment) in riwayat.details"
                                        :key="idx_segment" mb-2 wrap>
                                        <v-flex xs12>
                                          <v-layout v-if="riwayat.show_all === 'Y'" row>{{
                                            getnamelabel(segment.label)
                                          }}</v-layout>
                                          <v-layout v-if="
                                            riwayat.show_all === 'Y' &&
                                            segment.caption &&
                                            segment.caption !== ''
                                          " row><span class="caption red--text">{{
                                            segment.caption
                                          }}</span></v-layout>
                                          <v-layout pl-2 pb-1 pt-1 v-for="(
                                              r_detail, idx_detail
                                            ) in generate_rows(segment.details)" :key="idx_detail"
                                            v-if="riwayat.show_all === 'Y'" wrap>
                                            <v-flex xs12 sm6 pa-1>
                                              <v-layout v-if="r_detail[0]" align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="r_detail[0].chx" :disabled="riwayat.flag_normal === 'Y' ||
                                                    cantedit
                                                    " color="red" hide-details @change="
                                                      checkAnotherCbx(
                                                        r_detail[0].id_code,
                                                        $event,
                                                        idx_segment,
                                                        idx_r
                                                      )
                                                      "></v-checkbox>
                                                </v-flex>
                                                <v-flex xs6>
                                                  <span style="font-size: 12px;" :class="{
                                                    'red--text':
                                                      r_detail[0].color &&
                                                      r_detail[0].color == 'red',
                                                  }">{{
                                                    getnamelabel(
                                                      r_detail[0].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs5 v-if="r_detail[0].show_date" pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="r_detail[0].value" :disabled="riwayat.flag_normal === 'Y' ||
                                                      cantedit
                                                      " :readonly="!r_detail[0].chx" single-line :placeholder="getnamelabel('DD/MM/YYYY')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs12 sm6 pa-1>
                                              <v-layout v-if="r_detail[1]" align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="r_detail[1].chx" :disabled="riwayat.flag_normal === 'Y' ||
                                                    cantedit
                                                    " color="red" hide-details @change="
                                                      checkAnotherCbx(
                                                        r_detail[1].id_code,
                                                        $event,
                                                        idx_segment,
                                                        idx_r
                                                      )
                                                      "></v-checkbox>
                                                </v-flex>
                                                <v-flex xs6>
                                                  <span style="font-size: 12px;" :class="{
                                                    'red--text':
                                                      r_detail[1].color &&
                                                      r_detail[1].color == 'red',
                                                  }">{{
                                                    getnamelabel(
                                                      r_detail[1].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs5 v-if="r_detail[1].show_date" pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="r_detail[1].value" :readonly="!r_detail[1].chx"
                                                    :disabled="riwayat.flag_normal === 'Y' ||
                                                      cantedit
                                                      " single-line :placeholder="getnamelabel('DD/MM/YYYY')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                  </v-flex>
                                </v-layout>
                                <v-divider></v-divider>
                                <v-card-actions class="mr-3">
                                  <v-spacer></v-spacer>
                                  <v-btn v-if="riwayat.flag_normal === 'N'" :disabled="cantedit"
                                    @click="changeFlagNormal(idx_r)" small color="error"><v-icon class="pr-1"
                                      small>close</v-icon>
                                    {{
                                      getnamelabel(riwayat.label_flag_normal)
                                    }}</v-btn>
                                  <v-btn v-if="riwayat.flag_normal === 'Y'" :disabled="cantedit"
                                    @click="changeFlagNormal(idx_r)" small color="info"><v-icon class="pr-1"
                                      small>check</v-icon>
                                    {{
                                      getnamelabel(riwayat.label_flag_normal)
                                    }}</v-btn>
                                </v-card-actions>
                              </v-card>
                            </div>
                          </v-stepper-content>
                          <v-stepper-content class="x_form" step="2">
                            <div v-for="(fisik, idx_f) in fisiks" :key="idx_f">
                              <v-card id="stepbar_x" ref="stepbar_x" class="mb-2">
                                <v-layout pb-2 row>
                                  <v-flex xs12>
                                    <v-subheader red--text text--lighten-1>
                                      <v-btn class="hidden-xs-only" v-if="fisik.show_all === 'N'"
                                        @click="toggleDetailFisik(idx_f)" style="min-width: 30px;" small flat
                                        color="#fa8072"><v-icon small>add</v-icon></v-btn>
                                      <v-btn class="hidden-xs-only" v-if="fisik.show_all === 'Y'"
                                        @click="toggleDetailFisik(idx_f)" style="min-width: 30px;" small flat
                                        color="#fa8072"><v-icon small>remove</v-icon></v-btn>
                                      {{ getnamelabel(fisik.title) }}
                                      <v-flex text-md-right>
                                        <v-btn v-if="
                                          fisik.details[0].table_name === 'status_gizi'
                                        " :disabled="cantedit" @click="changeStandartBMI(idx_f)" small color="info">{{
                                          fisik.standart_bmi }}</v-btn>

                                        <v-btn v-if="
                                          fisik.type_form === 'TOOTH' &&
                                          fisik.is_normal === 'Y'
                                        " :disabled="cantedit" style="color: white;" @click="changeStatusTooth(idx_f)"
                                          small color="info">
                                          <v-icon class="pr-1" small>check</v-icon>
                                          {{ getnamelabel("TIDAK DIPERIKSA") }}
                                        </v-btn>
                                        <v-btn v-if="
                                          fisik.type_form === 'TOOTH' &&
                                          fisik.is_normal === 'N'
                                        " :disabled="cantedit" style="color: white;" @click="changeStatusTooth(idx_f)"
                                          small color="error">
                                          <v-icon class="pr-1" small>close</v-icon>
                                          {{ getnamelabel("TIDAK DIPERIKSA") }}
                                        </v-btn>

                                        <v-btn v-if="
                                          fisik.is_inspected &&
                                          fisik.is_inspected === 'N'
                                        " :disabled="cantedit" style="color: white;"
                                          @click="changeStatusInspected(idx_f)" small color="info">
                                          <v-icon class="pr-1" small>check</v-icon>
                                          {{ getnamelabel("TIDAK DIPERIKSA") }}
                                        </v-btn>
                                        <v-btn v-if="
                                          fisik.is_inspected &&
                                          fisik.is_inspected === 'Y'
                                        " :disabled="cantedit" style="color: white;"
                                          @click="changeStatusInspected(idx_f)" small color="error">
                                          <v-icon class="pr-1" small>close</v-icon>
                                          {{ getnamelabel("TIDAK DIPERIKSA") }}
                                        </v-btn>
                                      </v-flex>
                                    </v-subheader>
                                    <v-divider class="mb-2"></v-divider>
                                    <div v-if="fisik.type_form === 'TOOTH'">
                                      <v-layout wrap v-if="fisik.show_all === 'Y'">
                                        <v-flex pl-2 pt-2 pb-2 pr-2 xs12>
                                          <v-layout row>
                                            <v-flex xs12>
                                              <table>
                                                <!--<tr>
                                                                                                <th colspan="18">Gigi Geligi</th>
                                                                                            </tr>-->
                                                <tr class="bggrey">
                                                  <th></th>
                                                  <th class="text-xs-center" colspan="8">
                                                    {{
                                                      getnamelabel("GIGI KANAN")
                                                    }}
                                                  </th>
                                                  <th class="text-xs-center" colspan="8">
                                                    {{
                                                      getnamelabel("GIGI KIRI")
                                                    }}
                                                  </th>
                                                  <th></th>
                                                </tr>
                                                <tr>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                  <th v-for="gigiatasketiga in fisik
                                                    .details[0].details" class="thgigi">
                                                    <input type="text" :disabled="cantedit ||
                                                      fisik.is_normal === 'Y'
                                                      " v-model="gigiatasketiga.value" @change="
                                                        changeToothValue(
                                                          'atasketiga',
                                                          gigiatasketiga,
                                                          idx_f
                                                        )
                                                        " v-bind:class="{
                                                          'background-black':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'X',
                                                          'background-teal':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'C',
                                                          'background-brown':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'R',
                                                          'background-red':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'O',
                                                          'background-yellow-accent-4':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'A',
                                                          'background-info':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'K',
                                                          'background-purple':
                                                            gigiatasketiga.value.toUpperCase() ===
                                                            'I',
                                                        }" class="input_gigi mono" :readonly="!cantedit &&
                                                          fisik.is_normal !== 'Y'
                                                          " @click="
                                                            openChooseToothDialog(
                                                              'atasketiga',
                                                              gigiatasketiga,
                                                              idx_f
                                                            )
                                                            " />
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                </tr>
                                                <tr>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                  <th v-for="gigiataskedua in fisik
                                                    .details[1].details" class="thgigi">
                                                    <input type="text" v-model="gigiataskedua.value" :disabled="cantedit ||
                                                      fisik.is_normal === 'Y'
                                                      " @change="
                                                        changeToothValue(
                                                          'ataskedua',
                                                          gigiataskedua,
                                                          idx_f
                                                        )
                                                        " v-bind:class="{
                                                          'background-black':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'X',
                                                          'background-teal':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'C',
                                                          'background-brown':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'R',
                                                          'background-red':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'O',
                                                          'background-yellow-accent-4':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'A',
                                                          'background-info':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'K',
                                                          'background-purple':
                                                            gigiataskedua.value.toUpperCase() ===
                                                            'I',
                                                        }" class="input_gigi mono" :readonly="!cantedit &&
                                                          fisik.is_normal !== 'Y'
                                                          " @click="
                                                            openChooseToothDialog(
                                                              'ataskedua',
                                                              gigiataskedua,
                                                              idx_f
                                                            )
                                                            " />
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                </tr>
                                                <tr>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                  <th v-for="gigiatas in fisik.details[2]
                                                    .details" class="thgigi">
                                                    <input type="text" v-model="gigiatas.value" :disabled="cantedit ||
                                                      fisik.is_normal === 'Y'
                                                      " @change="
                                                        changeToothValue(
                                                          'atas',
                                                          gigiatas,
                                                          idx_f
                                                        )
                                                        " v-bind:class="{
                                                          'background-black':
                                                            gigiatas.value.toUpperCase() ===
                                                            'X',
                                                          'background-teal':
                                                            gigiatas.value.toUpperCase() ===
                                                            'C',
                                                          'background-brown':
                                                            gigiatas.value.toUpperCase() ===
                                                            'R',
                                                          'background-red':
                                                            gigiatas.value.toUpperCase() ===
                                                            'O',
                                                          'background-yellow-accent-4':
                                                            gigiatas.value.toUpperCase() ===
                                                            'A',
                                                          'background-info':
                                                            gigiatas.value.toUpperCase() ===
                                                            'K',
                                                          'background-purple':
                                                            gigiatas.value.toUpperCase() ===
                                                            'I',
                                                        }" class="input_gigi mono" :readonly="!cantedit &&
                                                          fisik.is_normal !== 'Y'
                                                          " @click="
                                                            openChooseToothDialog(
                                                              'atas',
                                                              gigiatas,
                                                              idx_f
                                                            )
                                                            " />
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                </tr>
                                                <tr class="bggrey">
                                                  <th>
                                                    {{ getnamelabel("ATAS") }}
                                                  </th>
                                                  <th v-for="gigiatas in fisik.details[0]
                                                    .details" class="thgigi">
                                                    {{ gigiatas.label }}
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("ATAS") }}
                                                  </th>
                                                </tr>
                                                <tr class="bggrey">
                                                  <th>
                                                    {{ getnamelabel("BAWAH") }}
                                                  </th>
                                                  <th v-for="gigibawah in fisik.details[1]
                                                    .details" class="thgigi">
                                                    {{ gigibawah.label }}
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("BAWAH") }}
                                                  </th>
                                                </tr>
                                                <tr>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                  <th v-for="gigibawah in fisik.details[3]
                                                    .details" class="thgigi">
                                                    <input type="text" v-model="gigibawah.value" :disabled="cantedit ||
                                                      fisik.is_normal === 'Y'
                                                      " @change="
                                                        changeToothValue(
                                                          'bawah',
                                                          gigibawah,
                                                          idx_f
                                                        )
                                                        " v-bind:class="{
                                                          'background-black':
                                                            gigibawah.value.toUpperCase() ===
                                                            'X',
                                                          'background-teal':
                                                            gigibawah.value.toUpperCase() ===
                                                            'C',
                                                          'background-brown':
                                                            gigibawah.value.toUpperCase() ===
                                                            'R',
                                                          'background-red':
                                                            gigibawah.value.toUpperCase() ===
                                                            'O',
                                                          'background-yellow-accent-4':
                                                            gigibawah.value.toUpperCase() ===
                                                            'A',
                                                          'background-info':
                                                            gigibawah.value.toUpperCase() ===
                                                            'K',
                                                          'background-purple':
                                                            gigibawah.value.toUpperCase() ===
                                                            'I',
                                                        }" class="input_gigi mono" :readonly="!cantedit &&
                                                          fisik.is_normal !== 'Y'
                                                          " @click="
                                                            openChooseToothDialog(
                                                              'bawah',
                                                              gigibawah,
                                                              idx_f
                                                            )
                                                            " />
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                </tr>
                                                <tr>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                  <th v-for="gigibawahkedua in fisik
                                                    .details[4].details" class="thgigi">
                                                    <input type="text" v-model="gigibawahkedua.value" :disabled="cantedit ||
                                                      fisik.is_normal === 'Y'
                                                      " @change="
                                                        changeToothValue(
                                                          'bawahkedua',
                                                          gigibawahkedua,
                                                          idx_f
                                                        )
                                                        " v-bind:class="{
                                                          'background-black':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'X',
                                                          'background-teal':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'C',
                                                          'background-brown':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'R',
                                                          'background-red':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'O',
                                                          'background-yellow-accent-4':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'A',
                                                          'background-info':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'K',
                                                          'background-purple':
                                                            gigibawahkedua.value.toUpperCase() ===
                                                            'I',
                                                        }" class="input_gigi mono" :readonly="!cantedit &&
                                                          fisik.is_normal !== 'Y'
                                                          " @click="
                                                            openChooseToothDialog(
                                                              'bawahkedua',
                                                              gigibawahkedua,
                                                              idx_f
                                                            )
                                                            " />
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                </tr>
                                                <tr>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                  <th v-for="gigibawahketiga in fisik
                                                    .details[5].details" class="thgigi">
                                                    <input type="text" v-model="gigibawahketiga.value" :disabled="cantedit ||
                                                      fisik.is_normal === 'Y'
                                                      " @change="
                                                        changeToothValue(
                                                          'bawahketiga',
                                                          gigibawahketiga,
                                                          idx_f
                                                        )
                                                        " v-bind:class="{
                                                          'background-black':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'X',
                                                          'background-teal':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'C',
                                                          'background-brown':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'R',
                                                          'background-red':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'O',
                                                          'background-yellow-accent-4':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'A',
                                                          'background-info':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'K',
                                                          'background-purple':
                                                            gigibawahketiga.value.toUpperCase() ===
                                                            'I',
                                                        }" class="input_gigi mono" :readonly="!cantedit &&
                                                          fisik.is_normal !== 'Y'
                                                          " @click="
                                                            openChooseToothDialog(
                                                              'bawahketiga',
                                                              gigibawahketiga,
                                                              idx_f
                                                            )
                                                            " />
                                                  </th>
                                                  <th>
                                                    {{ getnamelabel("KODE") }}
                                                  </th>
                                                </tr>
                                                <tr class="bggrey">
                                                  <th></th>
                                                  <th class="text-xs-center" colspan="8">
                                                    {{
                                                      getnamelabel("GIGI KANAN")
                                                    }}
                                                  </th>
                                                  <th class="text-xs-center" colspan="8">
                                                    {{
                                                      getnamelabel("GIGI KIRI")
                                                    }}
                                                  </th>
                                                  <th></th>
                                                </tr>
                                              </table>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout align-center pt-4 pl-2 row>
                                            <v-flex d-flex>
                                              <h4>
                                                {{
                                                  getnamelabel(
                                                    "KETERANGAN KODE"
                                                  )
                                                }}
                                                :
                                              </h4>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout align-center pt-3 pl-2 pb-3 row>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="black">
                                                    X
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel("BERLUBANG")
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="teal">
                                                    C
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel("TAMBALAN")
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="red">
                                                    O
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel("TANGGAL")
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="brown">
                                                    R
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel("SISA AKAR")
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout align-center pt-3 pl-2 pb-3 row>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="yellow accent-4">
                                                    A
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel("GIGI PALSU")
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="info">
                                                    K
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel(
                                                        "KARANG GIGI"
                                                      )
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs3>
                                              <v-layout align-center row>
                                                <v-flex xs4>
                                                  <v-btn small style="
                                                      min-width: 35px;
                                                      margin: 0;
                                                    " dark color="purple">
                                                    I
                                                  </v-btn>
                                                </v-flex>
                                                <v-flex xs8>
                                                  <h5>
                                                    {{
                                                      getnamelabel("IMPAKSI")
                                                    }}
                                                  </h5>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="fisik.type_form === 'XV'">
                                      <!--<v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{riwayat.subtitle}}</v-layout>-->
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in generate_rows(
                                        fisik.details
                                      )" v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex sm6 xs12 pa-1>
                                          <v-layout v-if="f_detail[0]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_detail[0].chx"
                                                @change="changeXVChx(idx_f, f_detail[0])" :disabled="cantedit"
                                                color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_detail[0].label)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " outline v-model="f_detail[0].value"
                                                @change="changeXVChx(idx_f, f_detail[0])" :disabled="cantedit"
                                                single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                        <v-flex sm6 xs12 pa-1>
                                          <v-layout v-if="f_detail[1]" align-center row>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_detail[1].chx"
                                                @change="changeXVChx(idx_f, f_detail[1])" :disabled="cantedit"
                                                color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_detail[1].label)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " outline v-model="f_detail[1].value"
                                                @change="changeXVChx(idx_f, f_detail[1])" :disabled="cantedit"
                                                single-line :placeholder="getnamelabel('Keterangan')"
                                                hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="fisik.type_form === 'MX'">
                                      <!--<v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{riwayat.subtitle}}</v-layout>-->
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in fisik.details"
                                        v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex xs12 pa-1>
                                          <v-layout align-center row>
                                            <v-flex xs7>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_detail.label)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1 pr-2 style=" display:inline-block!important;">
                                              <v-layout row wrap>
                                                <v-flex v-for="det_chx in f_detail.details">
                                                  <v-checkbox row class="mt-0 pt-0" v-model="det_chx.chx"
                                                    @change="changeMXChx(idx_f, kdx_detail, det_chx)"
                                                    :disabled="cantedit" color="red"
                                                    :label="`${getnamelabel(det_chx.label)}`" hide-details></v-checkbox>
                                                </v-flex>
                                              </v-layout>

                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="fisik.type_form === 'V'">
                                      <!--<v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{riwayat.subtitle}}</v-layout>-->
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in generate_rows(
                                        fisik.details
                                      )" v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex sm6 xs12 pa-1>
                                          <v-layout v-if="f_detail[0]" align-center row>
                                            <v-flex xs4>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_detail[0].label)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs5>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_detail[0].value" outline :disabled="cantedit" single-line
                                                hide-details></v-text-field>
                                            </v-flex>
                                            <v-flex xs3 pl-1>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_detail[0].unit)
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                        <v-flex sm6 xs12 pa-1>
                                          <v-layout v-if="f_detail[1]" align-center row>
                                            <v-flex xs4>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_detail[1].label)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs5>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_detail[1].value" outline :disabled="cantedit" single-line
                                                hide-details></v-text-field>
                                            </v-flex>
                                            <v-flex xs3 pl-1>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_detail[1].unit)
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="fisik.type_form === 'XVS'">
                                      <v-layout pl-2 v-for="(segment, idx_s) in fisik.details" wrap>
                                        <v-flex xs12>
                                          <v-layout v-if="fisik.show_all === 'Y'" row>{{
                                            getnamelabel(segment.name)
                                          }}</v-layout>
                                          <v-layout pl-2 pb-1 pt-1 v-for="(
                                              f_detail, kdx_detail
                                            ) in generate_rows(segment.details)" v-if="fisik.show_all === 'Y'" wrap>
                                            <v-flex sm6 xs12 pa-1>
                                              <v-layout v-if="f_detail[0]" align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="f_detail[0].chx" @change="
                                                    changeXVSChx(
                                                      idx_f,
                                                      idx_s,
                                                      f_detail[0]
                                                    )
                                                    " :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " color="red" hide-details></v-checkbox>
                                                </v-flex>
                                                <v-flex xs7>
                                                  <span style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_detail[0].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs4 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="f_detail[0].value" :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " single-line :placeholder="getnamelabel('Keterangan')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex sm6 xs12 pa-1>
                                              <v-layout v-if="f_detail[1]" align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="f_detail[1].chx" @change="
                                                    changeXVSChx(
                                                      idx_f,
                                                      idx_s,
                                                      f_detail[1]
                                                    )
                                                    " :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " color="red" hide-details></v-checkbox>
                                                </v-flex>
                                                <v-flex xs7>
                                                  <span style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_detail[1].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs4 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="f_detail[1].value" :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " single-line :placeholder="getnamelabel('Keterangan')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="fisik.type_form === 'XVS3R'">
                                      <v-layout pl-2 v-for="(segment, idx_s) in fisik.details" wrap>
                                        <v-flex xs12>
                                          <v-layout v-if="fisik.show_all === 'Y'" row>{{
                                            getnamelabel(segment.name)
                                          }}</v-layout>
                                          <v-layout pl-2 pb-1 pt-1 v-for="(
                                              f_detail, kdx_detail
                                            ) in generate_rows(segment.details)" v-if="fisik.show_all === 'Y'" wrap>
                                            <v-flex sm6 xs12 pa-1>
                                              <v-layout v-if="
                                                f_detail[0] &&
                                                f_detail[0].type_form === 'XVS'
                                              " align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="f_detail[0].chx" @change="
                                                    changeXVSChx(
                                                      idx_f,
                                                      idx_s,
                                                      f_detail[0]
                                                    )
                                                    " :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " color="red" hide-details></v-checkbox>
                                                </v-flex>
                                                <v-flex xs7>
                                                  <span style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_detail[0].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs4 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="f_detail[0].value" :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " single-line :placeholder="getnamelabel('Keterangan')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                              <v-layout v-if="
                                                f_detail[0] &&
                                                f_detail[0].type_form === 'V'
                                              " align-center row>
                                                <v-flex xs6>
                                                  <span style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_detail[0].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs6 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="f_detail[0].value" :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " single-line hide-details :suffix="getnamelabel(f_detail[0].unit)
                                                        "></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex sm6 xs12 pa-1>
                                              <v-layout v-if="
                                                f_detail[1] &&
                                                f_detail[1].type_form === 'V'
                                              " align-center row>
                                                <v-flex xs6>
                                                  <span style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_detail[1].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs6 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="f_detail[1].value" :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " single-line hide-details :suffix="getnamelabel(f_detail[1].unit)
                                                        "></v-text-field>
                                                </v-flex>
                                              </v-layout>

                                              <v-layout v-if="
                                                f_detail[1] &&
                                                f_detail[1].type_form === 'XVS'
                                              " align-center row>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="f_detail[1].chx" @change="
                                                    changeXVSChx(
                                                      idx_f,
                                                      idx_s,
                                                      f_detail[1]
                                                    )
                                                    " :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " color="red" hide-details></v-checkbox>
                                                </v-flex>
                                                <v-flex xs7>
                                                  <span style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_detail[1].label
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs4 pl-1 pr-2>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " outline v-model="f_detail[1].value" :disabled="cantedit ||
                                                      (fisik.is_inspected &&
                                                        fisik.is_inspected === 'N')
                                                      " single-line :placeholder="getnamelabel('Keterangan')
                                                        " hide-details></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                              <v-layout v-if="
                                                f_detail[1] &&
                                                f_detail[1].type_form === 'XVS-LXX'
                                              " align-center row>
                                                <v-flex xs12>
                                                  <v-layout row>
                                                    <v-flex xs1>
                                                      <v-checkbox row class="mt-0 pt-0" v-model="f_detail[1].chx"
                                                        @change="
                                                          changeXVSChxLXX(
                                                            idx_f,
                                                            idx_s,
                                                            f_detail[1]
                                                          )
                                                          " :disabled="cantedit ||
                                                            (fisik.is_inspected &&
                                                              fisik.is_inspected === 'N')
                                                            " color="red" hide-details></v-checkbox>
                                                    </v-flex>
                                                    <v-flex xs11>
                                                      <span style="font-size: 12px;">{{
                                                        getnamelabel(
                                                          f_detail[1].label
                                                        )
                                                      }}</span>
                                                    </v-flex>
                                                  </v-layout>
                                                  <v-layout row>
                                                    <v-flex class="pl-4" xs12>
                                                      <v-layout v-for="(
                                                          value_perut_14, idx_perut_14
                                                        ) in f_detail[1].details" class="pa-2" row>
                                                        <v-flex xs6 pl-4><span style="
                                                              font-size: 12px;
                                                            ">{{
                                                              value_perut_14.label
                                                            }}</span></v-flex>
                                                        <v-flex v-if="
                                                          _.has(
                                                            value_perut_14,
                                                            'chx_a_value'
                                                          )
                                                        " xs3>
                                                          <v-checkbox row class="mt-0 pt-0" color="red" label="D"
                                                            v-model="value_perut_14.chx_a_value
                                                              " :disabled="!f_detail[1].chx ||
                                                                cantedit ||
                                                                (fisik.is_inspected &&
                                                                  fisik.is_inspected ===
                                                                  'N')
                                                                " hide-details></v-checkbox>
                                                        </v-flex>
                                                        <v-flex v-if="
                                                          _.has(
                                                            value_perut_14,
                                                            'chx_b_value'
                                                          )
                                                        " xs3>
                                                          <v-checkbox row class="mt-0 pt-0" color="red" label="S"
                                                            v-model="value_perut_14.chx_b_value
                                                              " :disabled="!f_detail[1].chx ||
                                                                cantedit ||
                                                                (fisik.is_inspected &&
                                                                  fisik.is_inspected ===
                                                                  'N')
                                                                " hide-details></v-checkbox>
                                                        </v-flex>
                                                        <v-flex v-if="
                                                          _.has(
                                                            value_perut_14,
                                                            'chx_value'
                                                          )
                                                        " xs6>
                                                          <v-checkbox row class="mt-0 pt-0" color="red" label=""
                                                            v-model="value_perut_14.chx_value
                                                              " :disabled="!f_detail[1].chx ||
                                                                cantedit ||
                                                                (fisik.is_inspected &&
                                                                  fisik.is_inspected ===
                                                                  'N')
                                                                " hide-details></v-checkbox>
                                                        </v-flex>
                                                      </v-layout>
                                                    </v-flex>
                                                  </v-layout>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>

                                    <div v-if="fisik.type_form === 'VXX'">
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in generate_rows(
                                        fisik.details
                                      )" v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex v-for="f_cols in f_detail" sm6 xs12 pa-1>
                                          <v-layout v-if="f_cols.type === 'vxx-v'" align-center row>
                                            <v-flex xs4>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs5>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_cols.value" outline
                                                @change="changeVXXValue(idx_f, f_cols)" :disabled="cantedit" single-line
                                                hide-details></v-text-field>
                                            </v-flex>
                                            <v-flex xs3 pl-1>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.unit)
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout v-if="f_cols.type === 'vxx-xx'" align-center row>
                                            <v-flex xs4><span style="font-size: 12px;">{{
                                              getnamelabel(f_cols.label)
                                                }}</span></v-flex>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_y"
                                                @change="changeVXXChx(idx_f, f_cols, 'y')" :disabled="cantedit"
                                                color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs3>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label_y)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_x"
                                                @change="changeVXXChx(idx_f, f_cols, 'x')" :disabled="cantedit"
                                                color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs3>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label_x)
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="fisik.type_form === 'VXX+'">
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in generate_rows(
                                        fisik.details
                                      )" v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex v-for="f_cols in f_detail" sm6 xs12 pa-1>
                                          <v-layout v-if="f_cols.type === 'vxx-vv'" align-center row>
                                            <v-flex xs4>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label)
                                              }}</span><br />
                                              <v-btn @click="changStandartTensi(idx_f, f_cols)" style="
                                                  font-size: 10px;
                                                  height: 20px;
                                                  width: auto;
                                                  min-width: 5px;
                                                  margin: 0;
                                                " small v-if="f_cols.standart !== ''" color="info">
                                                {{ f_cols.standart }}
                                              </v-btn>
                                            </v-flex>
                                            <v-flex xs4>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_cols.value" outline @change="
                                                  changeVXXValuePlus(idx_f, f_cols)
                                                  " :disabled="cantedit" single-line hide-details></v-text-field>
                                              <span v-if="f_cols.standart !== ''" class="d-flex mt-1" style="
                                                  font-size: 10px;
                                                  font-weight: 600;
                                                " :class="getcolortensi(f_cols)">{{
                                                  getnamelabel(f_cols.value_x)
                                                }}</span>
                                            </v-flex>
                                            <v-flex xs4 pl-1>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.unit)
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout v-if="f_cols.type === 'vxx-vv2r'" align-center row>
                                            <v-flex xs4>
                                              <span style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label)
                                              }}</span><br />
                                              <v-btn @click="changStandartTensi(idx_f, f_cols)" style="
                                                  font-size: 10px;
                                                  height: 20px;
                                                  width: auto;
                                                  min-width: 5px;
                                                  margin: 0;
                                                " small v-if="f_cols.standart !== ''" color="info">
                                                {{ f_cols.standart }}
                                              </v-btn>
                                            </v-flex>
                                            <v-flex style="margin-left: -8px;" xs8>
                                              <v-layout class="pa-2 pl-0" align-center row>
                                                <!--<v-flex xs3>
                                                                                                <span style="font-size:12px" >{{getnamelabel(f_cols.label_x)}}</span>
                                                                                            </v-flex>-->
                                                <v-flex xs9>
                                                  <v-text-field :suffix="getnamelabel(f_cols.label_x)" style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " v-model="f_cols.value_x" outline @change="
                                                      changeVXXValuePlus2R(idx_f, f_cols)
                                                      " :disabled="cantedit" single-line hide-details></v-text-field>
                                                </v-flex>
                                                <v-flex xs3 pl-1>
                                                  <span class="mono" style="font-size: 12px;">{{
                                                    getnamelabel(f_cols.unit)
                                                  }}</span>
                                                </v-flex>
                                              </v-layout>
                                              <v-layout class="pa-2 pl-0" align-center row>
                                                <!--<v-flex xs3>
                                                                                                <span style="font-size:12px" >{{getnamelabel(f_cols.label_y)}}</span>
                                                                                            </v-flex>-->
                                                <v-flex xs9>
                                                  <v-text-field :suffix="getnamelabel(f_cols.label_y)" style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " v-model="f_cols.value_y" outline @change="
                                                      changeVXXValuePlus2R(idx_f, f_cols)
                                                      " :disabled="cantedit" single-line hide-details></v-text-field>
                                                </v-flex>
                                                <v-flex xs3 pl-1>
                                                  <span class="mono" style="font-size: 12px;">{{
                                                    getnamelabel(f_cols.unit)
                                                  }}</span>
                                                </v-flex>
                                              </v-layout>
                                              <span v-if="f_cols.standart !== ''" class="d-flex mt-1 pl-2" style="
                                                  font-size: 10px;
                                                  font-weight: 600;
                                                " :class="getcolortensi(f_cols)">{{
                                                  getnamelabel(f_cols.value)
                                                }}</span>
                                            </v-flex>
                                            <!--<v-flex xs4>
                                                                                        <v-text-field
                                                                                            style="font-size:12px;margin-top:0;padding-top:0;line-height:25px!important"
                                                                                            v-model="f_cols.value"
                                                                                            outline
                                                                                             @change="changeVXXValuePlus(idx_f,f_cols)"
                                                                                             :disabled="cantedit"
                                                                                            single-line
                                                                                            hide-details
                                                                                        ></v-text-field>
                                                                                       <span  v-if="f_cols.standart !==''" class='d-flex mt-1' 
                                                                                            style="font-size:10px;font-weight:600" 
                                                                                            :class="getcolortensi(f_cols)">{{getnamelabel(f_cols.value_x)}}</span> 
                                                                                    </v-flex>
                                                                                    <v-flex xs2 pl-1>
                                                                                        <span class="mono" style="font-size:12px" >{{getnamelabel(f_cols.unit)}}</span>
                                                                                    </v-flex>-->
                                          </v-layout>
                                          <v-layout v-if="f_cols.type === 'vxx-xx'" align-center row>
                                            <v-flex xs4><span style="font-size: 12px;">{{
                                              getnamelabel(f_cols.label)
                                                }}</span></v-flex>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_y"
                                                @change="changeVXXChx(idx_f, f_cols, 'y')" :disabled="cantedit"
                                                color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs3>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label_y)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_x"
                                                @change="changeVXXChx(idx_f, f_cols, 'x')" :disabled="cantedit"
                                                color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs3>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label_x)
                                              }}</span>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout v-if="f_cols.type === 'vxx-xv'" align-center row>
                                            <v-flex xs4><span style="font-size: 12px;">{{
                                              getnamelabel(f_cols.label)
                                                }}</span></v-flex>
                                            <v-flex xs1>
                                              <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_x"
                                                @change="changeVXXChx(idx_f, f_cols, 'x')" :disabled="cantedit || f_cols.value !== ''
                                                  " color="red" hide-details></v-checkbox>
                                            </v-flex>
                                            <v-flex xs3>
                                              <span class="mono" style="font-size: 12px;">{{
                                                getnamelabel(f_cols.label_x)
                                              }}</span>
                                            </v-flex>
                                            <v-flex xs4 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_cols.value" outline @change="
                                                  changeVXXValuePlusV(idx_f, f_cols)
                                                  " :disabled="cantedit || f_cols.chx_x" single-line
                                                hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                          <v-layout v-if="f_cols.type === 'vxx-xxv2r'" align-center row>
                                            <v-flex xs12>
                                              <v-layout class="mb-2" row>
                                                <v-flex xs4><span style="font-size: 12px;">{{
                                                  getnamelabel(f_cols.label)
                                                    }}</span></v-flex>
                                                <v-flex xs8 pr-3>
                                                  <v-text-field style="
                                                      font-size: 12px;
                                                      margin-top: 0;
                                                      padding-top: 0;
                                                      line-height: 25px !important;
                                                    " v-model="f_cols.value" :disabled="f_cols.disable_value === 'Y' ||
                                                      cantedit
                                                      " outline single-line hide-details
                                                    :suffix="f_cols.unit_name"></v-text-field>
                                                </v-flex>
                                              </v-layout>
                                              <v-layout row>
                                                <v-flex xs4></v-flex>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_y" @change="
                                                    changeVXXChx(idx_f, f_cols, 'y')
                                                    " :disabled="cantedit" color="red" hide-details></v-checkbox>
                                                </v-flex>
                                                <v-flex xs3>
                                                  <span class="mono" style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_cols.label_y
                                                    )
                                                  }}</span>
                                                </v-flex>
                                                <v-flex xs1>
                                                  <v-checkbox row class="mt-0 pt-0" v-model="f_cols.chx_x" @change="
                                                    changeVXXChx(idx_f, f_cols, 'x')
                                                    " :disabled="cantedit" color="red" hide-details></v-checkbox>
                                                </v-flex>
                                                <v-flex xs3>
                                                  <span class="mono" style="font-size: 12px;">{{
                                                    getnamelabel(
                                                      f_cols.label_x
                                                    )
                                                  }}</span>
                                                </v-flex>
                                              </v-layout>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="fisik.type_form === 'XXV'">
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in fisik.details"
                                        v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex xs12 pa-1>
                                          <v-layout align-center wrap>
                                            <v-flex sm4 xs12><span style="font-size: 12px;">{{
                                              getnamelabel(f_detail.label)
                                                }}</span></v-flex>
                                            <v-flex sm2 xs3>
                                              <v-layout align-center>
                                                <v-checkbox class="mt-0 pt-0 shrink mr-2" style="width: 15px;"
                                                  v-model="f_detail.chx_y" @change="
                                                    changeXXVChx(idx_f, f_detail, 'y')
                                                    " :disabled="cantedit" color="red" hide-details></v-checkbox>
                                                <span class="mono pl-1" style="font-size: 12px;">{{
                                                  getnamelabel(
                                                    f_detail.label_y
                                                  )
                                                }}</span>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex sm2 xs3>
                                              <v-layout align-center>
                                                <v-checkbox class="mt-0 pt-0 shrink mr-2" style="width: 15px;"
                                                  v-model="f_detail.chx_x" @change="
                                                    changeXXVChx(idx_f, f_detail, 'x')
                                                    " :disabled="cantedit" color="red" hide-details></v-checkbox>
                                                <span class="mono pl-1" style="font-size: 12px;">{{
                                                  getnamelabel(
                                                    f_detail.label_x
                                                  )
                                                }}</span>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex sm4 xs6 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_detail.value" :disabled="f_detail.disable_value === 'Y' ||
                                                  cantedit
                                                  " outline single-line hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                    <div v-if="fisik.type_form === 'XXVWL'">
                                      <v-layout pl-2 pb-1 pt-1 v-for="(f_detail, kdx_detail) in fisik.details"
                                        v-if="fisik.show_all === 'Y'" wrap>
                                        <v-flex xs12 pa-1>
                                          <v-layout align-center row>
                                            <v-flex xs3>
                                              <v-layout align-center>
                                                <v-checkbox class="mt-0 pt-0 shrink mr-2" style="width: 15px;"
                                                  v-model="f_detail.chx_y" @change="
                                                    changeXXVChx(idx_f, f_detail, 'y')
                                                    " :disabled="cantedit" color="red" hide-details></v-checkbox>
                                                <span class="mono pl-1" style="font-size: 12px;">{{
                                                  getnamelabel(
                                                    f_detail.label_y
                                                  )
                                                }}</span>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs3>
                                              <v-layout align-center>
                                                <v-checkbox class="mt-0 pt-0 shrink mr-2" style="width: 15px;"
                                                  v-model="f_detail.chx_x" @change="
                                                    changeXXVChx(idx_f, f_detail, 'x')
                                                    " :disabled="cantedit" color="red" hide-details></v-checkbox>
                                                <span class="mono pl-1" style="font-size: 12px;">{{
                                                  getnamelabel(
                                                    f_detail.label_x
                                                  )
                                                }}</span>
                                              </v-layout>
                                            </v-flex>
                                            <v-flex xs6 pr-2>
                                              <v-text-field style="
                                                  font-size: 12px;
                                                  margin-top: 0;
                                                  padding-top: 0;
                                                  line-height: 25px !important;
                                                " v-model="f_detail.value" :disabled="f_detail.disable_value === 'Y' ||
                                                  cantedit
                                                  " outline single-line hide-details></v-text-field>
                                            </v-flex>
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                  </v-flex>
                                </v-layout>
                              </v-card>
                            </div>
                            <v-card>
                              <v-layout row>
                                <v-flex xs12>
                                  <v-subheader red--text text--lighten-1>
                                    {{ getnamelabel("SARAN") }}</v-subheader>
                                  <v-divider></v-divider>
                                  <v-layout row wrap>
                                    <v-flex xs12 pa-2>
                                      <v-text-field v-model="umum_saran" :label="getnamelabel('Isikan saran') + '...'"
                                        :disabled="cantedit" outline></v-text-field>
                                    </v-flex>
                                  </v-layout>
                                </v-flex>
                              </v-layout>
                            </v-card>
                          </v-stepper-content>

                          <v-stepper-content class="x_form" step="3">
                            <div v-if="patient.details[0].template_name === 'Fisik Umum K3'" v-for="(k3, idx_k) in k3s">
                              <v-card class="mb-2">
                                <v-layout pb-2 row>
                                  <v-flex xs12>
                                    <v-subheader red--text text--lighten-1>
                                      <v-btn v-if="k3.show_all === 'N'" @click="toggleDetailK3(idx_k)"
                                        style="min-width: 30px;" small flat color="#fa8072"><v-icon
                                          small>add</v-icon></v-btn>
                                      <v-btn v-if="k3.show_all === 'Y'" @click="toggleDetailK3(idx_k)"
                                        style="min-width: 30px;" small flat color="#fa8072"><v-icon
                                          small>remove</v-icon></v-btn>
                                      {{ getnamelabel(k3.title) }}

                                      <v-flex text-md-right>
                                        <v-btn v-if="k3.is_notexist === 'Y'" :disabled="cantedit" style="color: white;"
                                          @click="changeExistPajanan(idx_k)" small color="info">
                                          <v-icon class="pr-1" small>check</v-icon>
                                          {{ getnamelabel(k3.label_notexist) }}
                                        </v-btn>
                                        <v-btn v-if="k3.is_notexist === 'N'" :disabled="cantedit" style="color: white;"
                                          @click="changeExistPajanan(idx_k)" small color="error">
                                          <v-icon class="pr-1" small>close</v-icon>
                                          {{ getnamelabel(k3.label_notexist) }}
                                        </v-btn>
                                      </v-flex>
                                    </v-subheader>
                                    <v-divider class="mb-2"></v-divider>
                                    <div v-if="k3.type_form === 'XVV'">
                                      <!--<v-layout v-if="riwayat.subtitle !== ''" class="pl-2 caption pb-2" row>{{riwayat.subtitle}}</v-layout>-->
                                      <v-layout pl-2 pb-1 pt-1 v-for="(k_detail, idk_detail) in k3.details"
                                        v-if="k3.show_all === 'Y'" wrap>
                                        <v-flex xs12>
                                          <v-layout align-center row>
                                            <v-flex xs12>
                                              <v-layout align-center>
                                                <v-checkbox style="width: 15px;" class="mt-0 pt-0 shrink mr-2"
                                                  :disabled="cantedit || k3.is_notexist === 'Y'
                                                    " v-model="k_detail.chx" color="red" hide-details></v-checkbox>
                                                <span class="pl-2" style="font-size: 12px;">{{
                                                  getnamelabel(k_detail.label)
                                                }}</span>
                                              </v-layout>
                                            </v-flex>
                                            <!----<v-flex xs3 pl-1 pr-2>
                                                                                        <v-text-field
                                                                                            style="font-size:12px;margin-top:0;padding-top:0;line-height:25px!important"
                                                                                            outline
                                                                                            :disabled="cantedit || k3.is_notexist === 'Y'"
                                                                                            v-model="k_detail.value_sumber"
                                                                                            single-line
                                                                                            :placeholder="getnamelabel('Sumber')"
                                                                                            hide-details
                                                                                        ></v-text-field>
                                                                                    </v-flex>-->
                                            <!----<v-flex xs3 pl-1 pr-2>
                                                                                        <v-text-field
                                                                                            style="font-size:12px;margin-top:0;padding-top:0;line-height:25px!important"
                                                                                            outline
                                                                                            :disabled="cantedit || k3.is_notexist === 'Y'"
                                                                                            v-model="k_detail.value_lama"
                                                                                            single-line
                                                                                            :placeholder="getnamelabel('Lama Pajanan')"
                                                                                            hide-details
                                                                                        ></v-text-field>
                                                                                    </v-flex>-->
                                          </v-layout>
                                        </v-flex>
                                      </v-layout>
                                    </div>
                                  </v-flex>
                                </v-layout>
                              </v-card>
                            </div>

                            <div v-if="patient.details[0].template_name === 'UMUM_KONSUL'">
                              <v-card>
                                <v-layout row>
                                  <v-flex xs12>
                                    <v-subheader red--text text--lighten-1>
                                      {{
                                        getnamelabel("KONSULTASI")
                                      }}</v-subheader>
                                    <v-divider></v-divider>
                                    <v-layout row wrap>
                                      <v-flex xs12 pa-2>
                                        <v-textarea v-for="(xkonsul, i) in konsul" :key="i" v-model="xkonsul.result"
                                          :label="getnamelabel(xkonsul.label).toUpperCase()
                                            " :disabled="cantedit" auto-grow outline></v-textarea>
                                      </v-flex>
                                    </v-layout>
                                  </v-flex>
                                </v-layout>
                              </v-card>
                            </div>
                          </v-stepper-content>
                        </v-stepper-items>
                      </v-stepper>

                      <v-card-actions>
                        <v-fab-transition>
                          <v-btn color="warning" dark absolute bottom right fab @click="goToUp()">
                            <v-icon>expand_less</v-icon>
                          </v-btn>
                        </v-fab-transition>
                        <v-btn color="error" @click="backStep(tab)" flat v-if="tab > 1">
                          Kembali
                        </v-btn>
                        <v-btn color="error" @click="nextStep(tab)" flat v-if="tab < tabs_fisik.length">
                          Lanjutkan
                        </v-btn>
                        <v-spacer></v-spacer>
                        <v-btn v-if="search_lab_no !== '' && tab === tabs_fisik.length" dark color="#2196f3" large
                          @click="saveResult(test, 'save')">Simpan</v-btn>
                      </v-card-actions>
                    </v-card>
                  </v-flex>
                </v-layout>
                <v-layout v-if="
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('6MWT')
                " row wrap>
                  <v-flex xs12>
                    <v-layout pt-1 pb-2 row>
                      <v-flex pt-1 pb-1 pl-2 pr-2 xs4>
                        <v-text-field label="BERAT BADAN (KG)" v-model="data_6mwt.bb" :disabled="cantedit"
                          @change="countBMI6mwt()" hide-details></v-text-field>
                      </v-flex>
                      <v-flex pt-1 pb-1 pl-2 pr-2 xs4>
                        <v-text-field label="TINGGI BADAN (CM)" v-model="data_6mwt.tb" :disabled="cantedit"
                          @change="countBMI6mwt()" hide-details></v-text-field>
                      </v-flex>
                      <v-flex pt-1 pb-1 pl-2 pr-2 xs4>
                        <v-text-field label="BMI (KG/M^2)" v-model="data_6mwt.bmi" :disabled="cantedit"
                          hide-details></v-text-field>
                      </v-flex>
                    </v-layout>
                    <v-layout pt-1 pb-2 row>
                      <v-flex pt-1 pb-1 pl-2 pr-2 xs6>
                        <v-text-field label="JARAK 1 PUTARAN (METER)" v-model="data_6mwt.distance" :disabled="cantedit"
                          hide-details></v-text-field>
                      </v-flex>
                      <v-flex pt-1 pb-1 pl-2 pr-2 xs6>
                        <v-text-field label="JUMLAH PUTARAN (PUTARAN)" v-model="data_6mwt.rounds" :disabled="cantedit"
                          hide-details></v-text-field>
                      </v-flex>
                    </v-layout>
                    <v-divider class="mt-2 mb-2"></v-divider>
                    <v-layout pt-1 pb-2 row>
                      <v-flex pt-1 pb-1 pl-2 pr-1 xs6>
                        <v-card class="pa-2">
                          <v-layout mb-2 row>
                            <v-flex class="text-xs-center" xs12>
                              <h4 class="subheading">PRE TEST</h4>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="TENSI" v-model="data_6mwt.pretest.tensi"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="SPO2" v-model="data_6mwt.pretest.spo2"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="NADI" v-model="data_6mwt.pretest.nadi"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="DYSPNEA" v-model="data_6mwt.pretest.dyspnea"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="FATIGUE" v-model="data_6mwt.pretest.fatigue"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                        </v-card>
                      </v-flex>
                      <v-flex pt-1 pb-1 pl-1 pr-2 xs6>
                        <v-card class="pa-2">
                          <v-layout mb-2 row>
                            <v-flex class="text-xs-center" xs12>
                              <h4 class="subheading">POST TEST</h4>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="TENSI" v-model="data_6mwt.posttest.tensi"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="SPO2" v-model="data_6mwt.posttest.spo2"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="NADI" v-model="data_6mwt.posttest.nadi"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="DYSPNEA" v-model="data_6mwt.posttest.dyspnea"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                          <v-layout pl-3 pr-3 row>
                            <v-flex xs12>
                              <v-text-field ma-1 label="FATIGUE" v-model="data_6mwt.posttest.fatigue"
                                :disabled="cantedit"></v-text-field>
                            </v-flex>
                          </v-layout>
                        </v-card>
                      </v-flex>
                    </v-layout>
                  </v-flex>
                </v-layout>
                <v-layout v-if="
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('6MWT')
                " class="ml-2 mr-2 mb-2" row>
                  <v-flex xs12>
                    <v-data-table :headers="headers_6mwt" :items="data_6mwt.details_6mwt" class="elevation-1"
                      hide-actions>
                      <template v-slot:items="props">
                        <td class="text-xs-right">{{ props.item.waktu }}</td>
                        <td class="text-xs-right">
                          <v-text-field ma-1 :disabled="cantedit" v-model="props.item.spo2"></v-text-field>
                        </td>
                        <td class="text-xs-right">
                          <v-text-field ma-1 v-model="props.item.nadi" :disabled="cantedit"></v-text-field>
                        </td>
                      </template>
                    </v-data-table>
                  </v-flex>
                </v-layout>
                <v-layout v-if="
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('6MWT')
                " class="pa-2" row>
                  <v-flex text-xs-center xs12>
                    <v-card>
                      <p class="headline pt-3 pb-1 font-weight-light">
                        {{ textvomax }}
                      </p>
                    </v-card>
                  </v-flex>
                </v-layout>
                <v-layout v-if="
                  test.template_flag_other === 'Y' &&
                  test.template_name.includes('6MWT')
                " class="pa-2" row>
                  <v-flex text-xs-center xs12>
                    <v-card color="blue-grey darken-2" class="white--text">
                      <p class="display-2 pt-3 pb-3 font-weight-bold">
                        {{ categoryvomax.toUpperCase() }}
                      </p>
                    </v-card>
                  </v-flex>
                </v-layout>
                <!-- TEMPLATE SDS -->
                <v-layout v-if="
                  test.template_flag_other === 'Y' && test.template_name.includes('SDS')
                " row wrap class="pa-2">
                  <div style="width: 100%;" class="pa-2">
                    <v-chip class="w-100 font-weight-bold subheading" style="width: 100%;" label text-color="white"
                      color="primary">IDENTITAS</v-chip>
                    <div class="pa-2">
                      <v-layout row wrap>
                        <v-flex xs4 class="pa-2">
                          <v-text-field :disabled="test.status !== 'NEW'" label="Nama"
                            v-model="data_sds.identitas.nama"></v-text-field>
                        </v-flex>
                        <v-flex xs4 class="pa-2">
                          <v-text-field :disabled="test.status !== 'NEW'" label="Usia"
                            v-model="data_sds.identitas.usia"></v-text-field>
                        </v-flex>
                        <v-flex xs4 class="pa-2">
                          <v-text-field :disabled="test.status !== 'NEW'" label="Masa Kerja "
                            v-model="data_sds.identitas.masa_kerja"></v-text-field>
                        </v-flex>
                        <v-flex xs4 class="pa-2">
                          <v-select :disabled="test.status !== 'NEW'" :items="data_sds.identitas.jenis_kelamin_option"
                            v-model="data_sds.identitas.jenis_kelamin" label="Jenis Kelamin*"></v-select>
                        </v-flex>
                        <v-flex xs4 class="pa-2">
                          <v-text-field :disabled="test.status !== 'NEW'" label="Departement"
                            v-model="data_sds.identitas.departement"></v-text-field>
                        </v-flex>
                        <v-flex xs4 class="pa-2">
                          <v-select :disabled="test.status !== 'NEW'" :items="data_sds.identitas.status_pekerja_option"
                            v-model="data_sds.identitas.status_pekerja" label="Status Pekerja*"></v-select>
                        </v-flex>
                        <v-flex xs3 class="pa-2">
                          <v-select :disabled="test.status !== 'NEW'" :items="data_sds.identitas.jenis_pekerjaan_option"
                            v-model="data_sds.identitas.jenis_pekerjaan" label="Jenis Pekerjaan*"></v-select>
                        </v-flex>
                        <v-flex xs3 class="pa-2">
                          <v-select :disabled="test.status !== 'NEW'" :items="data_sds.identitas.level_jabatan_option"
                            v-model="data_sds.identitas.level_jabatan" label="Level Jabatan*"></v-select>
                        </v-flex>
                        <v-flex xs3 class="pa-2">
                          <v-select :disabled="test.status !== 'NEW'" :items="data_sds.identitas.pendidikan_option"
                            v-model="data_sds.identitas.pendidikan" label="Pendidikan*"></v-select>
                        </v-flex>
                        <v-flex xs3 class="pa-2">
                          <v-select :disabled="test.status !== 'NEW'"
                            :items="data_sds.identitas.status_perkawinan_option"
                            v-model="data_sds.identitas.status_perkawinan" label="Status perkawinan*"></v-select>
                        </v-flex>
                      </v-layout>
                    </div>
                    <v-toolbar dark color="primary" dense flat>
                      <v-toolbar-title>Survey Diagnostic Stress (SDS 30)</v-toolbar-title>
                      <v-spacer></v-spacer>
                      <v-toolbar-items>
                        <v-btn dark @click="changeTypeSDS('SDS30')" v-if="type_sds_form === 'SRQ20'" small
                          color="orange">
                          <v-icon dark>close</v-icon> Tidak Aktif</v-btn>
                        <v-btn dark v-if="type_sds_form === 'SDS30'" small color="success"><v-icon dark>check</v-icon>
                          Aktif</v-btn>
                      </v-toolbar-items>
                    </v-toolbar>
                    <!--<v-chip
                      class="w-100 font-weight-bold subheading pa-2"
                      style="width: 100%"
                      label
                      text-color="white"
                      color="primary"
                      >
                      <v-avatar >
                        <v-icon v-if="type_sds_form === 'SDS30'">check</v-icon>
                        <v-icon  click="changeTypeSDS('SDS30')" v-if="type_sds_form === 'SRQ20'">close</v-icon>
                      </v-avatar>
                      Survey Diagnostic Stress (SDS 30)
                      
                      </v-chip
                    >-->

                    <v-layout v-for="(qst, i) in data_sds.sds30.questions" v-if="type_sds_form === 'SDS30'" row
                      class="pa-2" :key="test.status + type_sds_form">
                      <v-flex shrink pa-1 class="mr-2 font-weight-bold">
                        {{ qst.orderNumber }}
                      </v-flex>
                      <v-flex grow pa-1>
                        <div class="font-weight-bold">
                          {{ qst.display }}
                        </div>

                        <v-layout row wrap>
                          <v-radio-group @change="interpretationSds()" hide-details v-if="test.status && type_sds_form"
                            row v-model="qst.value">
                            <v-radio v-for="(opt, i) in data_sds.sds30.options" :key="i" class="mb-1"
                              :label="opt.display" :value="opt.id"></v-radio>
                          </v-radio-group>
                        </v-layout>
                      </v-flex>
                    </v-layout>
                    <div v-if="type_sds_form === 'SDS30'" class="pa-2">
                      <v-data-table :headers="headerSds" :items="data_sds.sds30.interpretation" hide-actions
                        class="elevation-1">
                        <template slot="headerCell" slot-scope="props">
                          <span>
                            {{ props.header.text }}
                          </span>
                        </template>
                        <template v-slot:items="props">
                          <td>{{ props.item.display }}</td>
                          <td class="text-xs-center">
                            {{ props.item.score }}
                          </td>
                          <td class="text-xs-center">
                            {{ props.item.levelDisplay }}
                          </td>
                        </template>
                      </v-data-table>
                    </div>
                    <v-toolbar class="mt-2" dense dark color="primary" flat>
                      <v-toolbar-title>Survey Reporting Questioner (SRQ 20)</v-toolbar-title>
                      <v-spacer></v-spacer>
                      <v-toolbar-items>
                        <v-btn @click="changeTypeSDS('SRQ20')" v-if="type_sds_form === 'SDS30'" dark small
                          color="orange">
                          <v-icon dark>close</v-icon> Tidak Aktif</v-btn>
                        <v-btn dark v-if="type_sds_form === 'SRQ20'" small color="success"><v-icon dark>check</v-icon>
                          Aktif</v-btn>
                      </v-toolbar-items>
                    </v-toolbar>
                    <!--<v-chip
                      class="pa-2 w-100 font-weight-bold subheading"
                      style="width: 100%"
                      label
                      text-color="white"
                      color="primary"
                      >
                      <v-avatar>
                        <v-icon  v-if="type_sds_form === 'SRQ20'">check</v-icon>
                        <v-icon click="changeTypeSDS('SRQ20')" v-if="type_sds_form === 'SDS30'">close</v-icon>
                      </v-avatar>
                      Survey Reporting Questioner (SRQ 20)</v-chip
                    >-->
                    <v-layout v-if="type_sds_form === 'SRQ20'" v-for="(qst, i) in data_sds.srq20.questions" row
                      class="pa-2">
                      <v-flex shrink pa-1 class="mr-2 font-weight-bold">
                        {{ qst.orderNumber }}
                      </v-flex>
                      <v-flex grow pa-1>
                        <div class="font-weight-bold">
                          {{ qst.display }}
                        </div>

                        <div>
                          <v-radio-group @change="interpretationSrq()" hide-details :key="test.status + type_sds_form"
                            row v-model="qst.value">
                            <v-radio v-for="(opt, i) in data_sds.srq20.options" :key="i" class="mb-1"
                              :label="opt.display" :value="opt.id"></v-radio>
                          </v-radio-group>
                        </div>
                      </v-flex>
                    </v-layout>
                    <div v-if="type_sds_form === 'SRQ20'" class="pa-2">
                      <v-data-table :headers="headerSrq" :items="data_sds.srq20.interpretation" hide-actions
                        class="elevation-1">
                        <template slot="headerCell" slot-scope="props">
                          <span>
                            {{ props.header.text }}
                          </span>
                        </template>
                        <template v-slot:items="props">
                          <td>{{ props.item.display }}</td>
                          <td class="text-xs-center">
                            {{ props.item.score }}
                          </td>
                          <td class="text-xs-center">
                            {{ props.item.levelDisplay }}
                          </td>
                        </template>
                      </v-data-table>
                    </div>
                  </div>
                </v-layout>
              </div>


            </v-flex>
          </v-layout>
          <v-divider v-if="!test.template_name.includes('Umum')"></v-divider>
          <v-layout v-if="!test.template_name.includes('Umum')" align-center justify-end row>
            <v-flex xs12 pl-3 pr-3 pt-2 pb-2>
              <v-combobox v-model="test.status_result" :items="test.status_result_arr" :disabled="test.status !== 'NEW'"
                label="Kategori Hasil" item-text="name" multiple chips></v-combobox>
            </v-flex>
          </v-layout>
        </v-card>
      </v-flex>
    </v-layout>


    <v-dialog v-model="dialog_choose_tooth" scrollable max-width="300px">
      <v-card>
        <v-card-title>Pilih Kelainan</v-card-title>
        <v-divider></v-divider>
        <v-card-text style="height: 300px;">
          <v-radio-group v-model="kelainan_gigi" column>
            <v-radio color="black" label="Berlubang" value="X"></v-radio>
            <v-radio color="teal" label="Tambalan" value="C"></v-radio>
            <v-radio color="red" label="Tanggal" value="O"></v-radio>
            <v-radio color="brown" label="Sisa Akar" value="R"></v-radio>
            <v-radio color="yellow" label="Gigi Palsu" value="A"></v-radio>
            <v-radio color="blue" label="Karang Gigi" value="K"></v-radio>
            <v-radio color="purple" label="Impaksi" value="I"></v-radio>

            <v-radio color="primary" class="mt-3" value="">
              <template v-slot:label>
                <div>-- <strong class="primary--text">Reset</strong> --</div>
              </template>
            </v-radio>
          </v-radio-group>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey darken-1" flat @click="dialog_choose_tooth = false">Batal</v-btn>
          <v-btn color="blue darken-1" flat @click="saveSelectedTooth()">Pilih</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <template>
      <v-dialog v-model="openprint" style="overflow: hidden !important;" fullscreen>
        <v-card>
          <v-card-text>
            <object type="application/pdf" style="overflow: hidden; min-height: 100vh; width: 100%;"
              :data="urlprint"></object>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>

            <v-btn color="green darken-1" flat="flat" @click="closePrint">Tutup</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </template>
    <v-dialog v-model="dialog_alert_info" width="500">
      <v-card>
        <v-card-title class="headline error lighten-2" primary-title>
          Perhatian !
        </v-card-title>

        <v-card-text v-html="msg_alert_info"> </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" flat @click="dialog_alert_info = false">
            Tutup
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <!-- <one-dialog-print
      :title="printtitle"
      :width="printwidth"
      :height="500"
      :status="openprint"
      :urlprint="urlprint"
      @close-dialog-print="closePrint"
    ></one-dialog-print> -->
  </div>
</template>

<style scoped>
.divider.x_step {
  border-color: #fff;
}

.x_form .v-text-field--outline>.v-input__control>.v-input__slot {
  align-items: stretch;
  min-height: 20px !important;
  margin-top: 0;
  line-height: 25px;
}

.x_form .v-text-field--outline.v-text-field--single-line input {
  margin-top: 0px !important;
  font-size: 14px;
}

table,
td,
th {
  border: 1px solid #ddd;
  text-align: left;
}

table {
  border-collapse: collapse;
  width: 100%;
}

th,
td {
  padding-top: 5px;
  padding-bottom: 5px;
  padding-left: 8px;
  padding-right: 5px;
}

.mini-input .v-input {
  margin-top: 0px;
}

.mini-input .v-input,
.mini-input .v-input--selection-controls,
.mini-input .v-input__slot {
  margin-top: 0px;
  margin-bottom: 0px;
  margin-left: 3px;
}

.mini-input .v-messages {
  min-height: 0px;
}

input.fhm-input {
  border: 1px solid black;
  border-radius: 2px;
  -webkit-box-shadow: inset 0 0 2px rgba(0, 0, 0, 0.1),
    0 0 4px rgba(0, 0, 0, 0.1);
  -moz-box-shadow: inset 0 0 2px rgba(0, 0, 0, 0.1),
    0 0 4px rgba(0, 0, 0, 0.1);
  box-shadow: inset 0 0 2px rgba(0, 0, 0, 0.1), 0 0 4px rgba(0, 0, 0, 0.1);
  padding: 2px 4px;
  background: rgba(255, 255, 255, 0.5);
  margin: 0 0 1px 0;
  width: 30px;
  text-align: center;
}

th,
td {
  padding-left: 15px;
  padding-right: 15px;
  padding-top: 10px;
  padding-bottom: 10px;
  text-align: left;
}

table.tform tr:nth-child(even) {
  background-color: #eee;
}

table.tform tr:nth-child(odd) {
  background-color: #fff;
}

.thgigi {
  width: 50px !important;
  padding: 5px !important;
  text-align: center;
}

.input_gigi {
  width: 100%;
  border: 1px solid grey;
  font-size: 14px;
  text-align: center;
  line-height: 2;
  text-transform: uppercase;
}

.bggrey {
  background: #eee !important;
}

.background-black {
  background: black;
  color: #fff;
}

.background-teal {
  background: #009688;
  color: #fff;
}

.background-red {
  background: #f44336;
  color: #fff;
}

.background-brown {
  background: #795548;
  color: #fff;
}

.background-yellow-accent-4 {
  background: #ffeb3b;
  color: #fff;
}

.background-info {
  background: #64b5f6;
  color: #fff;
}

.background-purple {
  background: #9c27b0;
  color: #fff;
}
</style>

<script>
module.exports = {
  components: {
    "one-dialog-print": httpVueLoader("../../common/oneDialogPrintX.vue"),
  },
  data: () => ({
    dialog_alert_info: false,
    msg_alert_info: "",
    kelainan_gigi: false,
    dialog_choose_tooth: false,
    tooth_icx: null,
    tooth_pos: null,
    tooth_idx: null,
    search_doctor: "",
    dialog_warning_signature: false,
    dialogdownload: false,
    reports: [],
    selected_download: "",
    openprint: false,
    //urlprint:'',
    printtitle: "",
    printwidth: 600,
    requiredoctor: false,
    tab: 1,
    cbx_1: false,
    dialoglang: false,
    xnowtest: {},
    template_xname: "",
    headers_6mwt: [
      { text: "WAKTU", sortable: false, width: "30%" },
      { text: "SPO2", sortable: false },
      { text: "NADI", sortable: false },
    ],
    headerSds: [
      {
        text: "KOMPONEN STRESS KERJA",
        value: "string",
        sortable: false,
        align: "center",
        class: "font-font-weight-bold pa-2 primary  white--text",
        width: "60%",
      },
      {
        text: "SKOR",
        value: "string",
        sortable: false,
        align: "center",
        class: "font-font-weight-bold pa-2 primary  white--text",
        width: "20%",
      },
      {
        text: "TINGKAT STRESS",
        value: "string",
        align: "center",
        sortable: false,
        class: "font-font-weight-bold pa-2 primary  white--text",
        width: "20%",
      },
    ],
    headerSrq: [
      {
        text: "GEJALA",
        value: "string",
        sortable: false,
        align: "center",
        class: "font-font-weight-bold pa-2 primary  white--text",
        width: "60%",
      },
      {
        text: "SKOR",
        value: "string",
        sortable: false,
        align: "center",
        class: "font-font-weight-bold pa-2 primary  white--text",
        width: "20%",
      },
      {
        text: "NTEPRETASI",
        value: "string",
        align: "center",
        sortable: false,
        class: "font-font-weight-bold pa-2 primary  white--text",
        width: "20%",
      },
    ],
  }),
  mounted() {
    //this.draw();
  },
  computed: {
    type_sds_form: {
      get() {
        return this.$store.state.sample.type_sds_form;
      },
      set(val) {
        this.$store.commit("sample/update_type_sds_form", val);
      },
    },
    search_lab_no() {
      return this.$store.state.sample.search_lab_no;
    },
    dialog_xsignature: {
      get() {
        return this.$store.state.sample.dialog_xsignature;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_xsignature", val);
      },
    },
    categoryvomax() {
      var rtn = "-";
      if (this.vo2max > 0) {
        if (this.patient.sexcode == "L") {
          if (this.umur >= 18 && this.umur <= 25) {
            if (this.vo2max > 60) rtn = "Excellent";
            if (this.vo2max >= 52 && this.vo2max < 61) rtn = "Good";
            if (this.vo2max >= 47 && this.vo2max < 52)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 42 && this.vo2max < 47) rtn = "Rata-rata";
            if (this.vo2max >= 37 && this.vo2max < 42)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 30 && this.vo2max < 37)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 30) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 26 && this.umur <= 35) {
            if (this.vo2max > 56) rtn = "Excellent";
            if (this.vo2max >= 49 && this.vo2max < 57) rtn = "Good";
            if (this.vo2max >= 43 && this.vo2max < 49)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 40 && this.vo2max < 43) rtn = "Rata-rata";
            if (this.vo2max >= 35 && this.vo2max < 40)
              //rtn = 'Bellow Average'
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 30 && this.vo2max < 35)
              //rtn = 'Poor'
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 30)
              //rtn = 'Very Poor'
              rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 36 && this.umur <= 45) {
            if (this.vo2max > 51) rtn = "Excellent";
            if (this.vo2max >= 43 && this.vo2max < 52) rtn = "Good";
            if (this.vo2max >= 39 && this.vo2max < 43)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 35 && this.vo2max < 39) rtn = "Rata-rata";
            if (this.vo2max >= 31 && this.vo2max < 35)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 26 && this.vo2max < 31)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 26) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 46 && this.umur <= 55) {
            if (this.vo2max > 45) rtn = "Excellent";
            if (this.vo2max >= 39 && this.vo2max < 46) rtn = "Good";
            if (this.vo2max >= 36 && this.vo2max < 39)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 32 && this.vo2max < 36) rtn = "Rata-rata";
            if (this.vo2max >= 29 && this.vo2max < 32)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 25 && this.vo2max < 29)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 25) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 56 && this.umur <= 65) {
            if (this.vo2max > 41) rtn = "Excellent";
            if (this.vo2max >= 36 && this.vo2max < 42) rtn = "Good";
            if (this.vo2max >= 32 && this.vo2max < 36)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 30 && this.vo2max < 32) rtn = "Rata-rata";
            if (this.vo2max >= 26 && this.vo2max < 30)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 22 && this.vo2max < 26)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 22) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur > 65) {
            if (this.vo2max > 37) rtn = "Excellent";
            if (this.vo2max >= 33 && this.vo2max < 38) rtn = "Good";
            if (this.vo2max >= 29 && this.vo2max < 33)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 26 && this.vo2max < 29) rtn = "Rata-rata";
            if (this.vo2max >= 22 && this.vo2max < 26)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 20 && this.vo2max < 22)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 20) rtn = "Di bawah batas nilai normal";
          }
        }
        if (this.patient.sexcode == "P") {
          if (this.umur >= 18 && this.umur <= 25) {
            if (this.vo2max > 56) rtn = "Excellent";
            if (this.vo2max >= 47 && this.vo2max < 57) rtn = "Good";
            if (this.vo2max >= 42 && this.vo2max < 47)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 38 && this.vo2max < 42) rtn = "Rata-rata";
            if (this.vo2max >= 33 && this.vo2max < 38)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 28 && this.vo2max < 33)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 28) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 26 && this.umur <= 35) {
            if (this.vo2max > 52) rtn = "Excellent";
            if (this.vo2max >= 45 && this.vo2max < 53) rtn = "Good";
            if (this.vo2max >= 39 && this.vo2max < 45)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 35 && this.vo2max < 39) rtn = "Rata-rata";
            if (this.vo2max >= 31 && this.vo2max < 35)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 26 && this.vo2max < 31)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 26) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 36 && this.umur <= 45) {
            if (this.vo2max > 45) rtn = "Excellent";
            if (this.vo2max >= 38 && this.vo2max < 46) rtn = "Good";
            if (this.vo2max >= 34 && this.vo2max < 38)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 31 && this.vo2max < 34) rtn = "Rata-rata";
            if (this.vo2max >= 27 && this.vo2max < 31)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 22 && this.vo2max < 27)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 22) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 46 && this.umur <= 55) {
            if (this.vo2max > 40) rtn = "Excellent";
            if (this.vo2max >= 34 && this.vo2max < 41) rtn = "Good";
            if (this.vo2max >= 31 && this.vo2max < 34)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 28 && this.vo2max < 31) rtn = "Rata-rata";
            if (this.vo2max >= 25 && this.vo2max < 28)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 20 && this.vo2max < 25)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 20) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur >= 56 && this.umur <= 65) {
            if (this.vo2max > 37) rtn = "Excellent";
            if (this.vo2max >= 32 && this.vo2max < 38) rtn = "Good";
            if (this.vo2max >= 28 && this.vo2max < 32)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 25 && this.vo2max < 28) rtn = "Rata-rata";
            if (this.vo2max >= 22 && this.vo2max < 25)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 18 && this.vo2max < 22)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 18) rtn = "Di bawah batas nilai normal";
          }
          if (this.umur > 65) {
            if (this.vo2max > 32) rtn = "Excellent";
            if (this.vo2max >= 28 && this.vo2max < 33) rtn = "Good";
            if (this.vo2max >= 25 && this.vo2max < 28)
              rtn = "Di atas rata-rata";
            if (this.vo2max >= 22 && this.vo2max < 25) rtn = "Rata-rata";
            if (this.vo2max >= 19 && this.vo2max < 22)
              rtn = "Di bawah rata-rata";
            if (this.vo2max >= 17 && this.vo2max < 19)
              rtn = "Di bawah batas nilai normal";
            if (this.vo2max < 17) rtn = "Di bawah batas nilai normal";
          }
        }
      }

      return rtn;
    },
    textvomax() {
      //console.log('dasdasdas')
      var rtn = "-";
      //console.log(this.vo2max)
      if (this.vo2max > 0)
        rtn =
          "VO2 MAX = (0.06 x " +
          this.data_6mwt.distance +
          " x " +
          this.data_6mwt.rounds +
          ") - (0.104 x " +
          this.umur +
          ") + (0.052 x " +
          this.data_6mwt.bb +
          ") + 2.9 = " +
          this.vo2max;

      return rtn;
    },
    umur() {
      var rtn = 0;
      if (!_.isEmpty(this.patient)) {
        var age = this.patient.age;
        var res = age.split(" ");
        rtn = Math.floor(
          (parseInt(res[0]) + parseFloat(res[2] / 12)).toFixed(1)
        );
      }
      return rtn;
    },
    vo2max() {
      console.log("ikiki");
      var rtn = 0;
      var datax = this.$store.state.sample.data_6mwt;
      console.log(datax);
      if (datax) {
        var xusia = this.umur;
        if (
          parseInt(datax.distance) > 0 &&
          parseInt(datax.rounds) &&
          parseInt(datax.bb) > 0
        )
          rtn = (
            0.06 * (datax.distance * datax.rounds) -
            0.104 * xusia +
            0.052 * datax.bb +
            2.9
          ).toFixed(2);
      }
      return rtn;
    },
    data_6mwt() {
      return this.$store.state.sample.data_6mwt;
    },
    data_sds: {
      get() {
        return this.$store.state.sample.data_sds;
      },
      set(val) {
        this.$store.commit("sample/update_data_sds", val);
      },
    },
    dialognote: {
      get() {
        return this.$store.state.sample.dialog_note;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_note", val);
      },
    },
    urlprint: {
      get() {
        return this.$store.state.sample.url_print;
      },
      set(val) {
        this.$store.commit("sample/update_url_print", val);
      },
    },
    standart_bmi() {
      return this.$store.state.sample.standart_bmi;
    },
    selected_standart_bmi: {
      get() {
        return this.$store.state.sample.selected_standart_bmi;
      },
      set(val) {
        this.$store.commit("sample/update_selected_standart_bmi", val);
      },
    },
    cantedit: {
      get() {
        return this.$store.state.sample.cantedit;
      },
      set(val) {
        this.$store.commit("sample/update_cantedit", val);
      },
    },
    tabs_fisik: {
      get() {
        return this.$store.state.sample.tabs_fisik;
      },
      set(val) {
        this.$store.commit("sample/update_tabs_fisik", val);
      },
    },
    umum_saran: {
      get() {
        return this.$store.state.sample.umum_saran;
      },
      set(val) {
        this.$store.commit("sample/update_umum_saran", val);
      },
    },
    konsul: {
      get() {
        return this.$store.state.sample.konsul;
      },
      set(val) {
        this.$store.commit("sample/update_konsul", val);
      },
    },
    rows_riwayat() {
      return this.$store.state.sample.rows_riwayat;
    },
    riwayats: {
      get() {
        return this.$store.state.sample.riwayats;
      },
      set(val) {
        this.$store.commit("sample/update_riwayats", val);
      },
    },
    fisiks: {
      get() {
        return this.$store.state.sample.fisiks;
      },
      set(val) {
        this.$store.commit("sample/update_fisiks", val);
      },
    },
    k3s: {
      get() {
        return this.$store.state.sample.k3s;
      },
      set(val) {
        this.$store.commit("sample/update_k3s", val);
      },
    },
    xdialogaction: {
      get() {
        return this.$store.state.sample.dialog_action;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_action", val);
      },
    },
    xmsgaction: {
      get() {
        return this.$store.state.sample.msg_action;
      },
      set(val) {
        this.$store.commit("sample/update_msg_action", val);
      },
    },
    xtests() {
      return this.$store.state.sample.selected_transaction.details;
    },
    xlangs() {
      return this.$store.state.sample.langs;
    },
    patient() {
      return this.$store.state.sample.selected_transaction;
    },
    xdialogimage: {
      get() {
        return this.$store.state.sample.dialog_image;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_image", val);
      },
    },
    ximage() {
      return this.$store.state.sample.image;
    },
    xdoctors() {
      return this.$store.state.sample.doctors;
    },
    xselecteddoctor: {
      get() {
        return this.$store.state.sample.selected_doctor;
      },
      set(val) {
        this.$store.commit("sample/update_selected_doctor", val);
      },
    },
    xdialogdoctor: {
      get() {
        return this.$store.state.sample.dialog_doctor;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_doctor", val);
      },
    },
    dialogtemplates: {
      get() {
        return this.$store.state.sample.dialog_template;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_template", val);
      },
    },
    xtemplates() {
      return this.$store.state.sample.templates;
    },
    xselectedtemplate: {
      get() {
        return this.$store.state.sample.selected_template;
      },
      set(val) {
        this.$store.commit("sample/update_selected_template", val);
      },
    },
    dialoglangs: {
      get() {
        return this.$store.state.sample.langs;
      },
      set(val) {
        this.$store.commit("sample/update_item_langs", val);
      },
    },
    xselectedlang: {
      get() {
        return this.$store.state.sample.selected_lang;
      },
      set(val) {
        this.$store.commit("sample/update_selected_lang", val);
      },
    },
    sellang: {
      get() {
        return this.$store.state.sample.sellang;
      },
      set(val) {
        this.$store.commit("sample/update_sellang", val);
      },
    },
    dialogprintlang: {
      get() {
        return this.$store.state.sample.dialog_print_lang;
      },
      set(val) {
        this.$store.commit("sample/update_dialog_print_lang", val);
      },
    },
    doctors: {
      get() {
        return this.$store.state.sample.doctors;
      },
      set(val) {
        this.$store.commit("sample/update_doctors", val);
      },
    },
    selected_doctor: {
      get() {
        return this.$store.state.sample.selected_doctor;
      },
      set(val) {
        this.$store.commit("sample/update_selected_doctor", val);
      },
    },
  },
  methods: {
    changeTypeSDS(value) {
      console.log(value);
      console.log("xxxx");
      this.type_sds_form = value;
      console.log(this.type_sds_form);
      var arr = this.$store.state.sample.selected_transaction;
      var prm = { type: this.type_sds_form, reid: arr.re_id };
      this.$store.dispatch("sample/savetypesds", prm);
    },
    compareNumbers(num1, num2, operator) {
      switch (operator) {
        case ">":
          return num1 > num2;
        case "<":
          return num1 < num2;
        case ">=":
          return num1 >= num2;
        case "<=":
          return num1 <= num2;
        case "==":
          return num1 == num2;
        case "===":
          return num1 === num2;
        case "=":
          return num1 === num2;
        case "!=":
          return num1 != num2;
        case "!==":
          return num1 !== num2;
        default:
          return false;
      }
    },
    interpretationSds() {
      let tmp = this.data_sds;

      tmp.sds30.interpretation.forEach((k) => {
        var arrQid = k.questionID.split(",");
        k.score = 0;
        tmp.sds30.questions.forEach((e) => {
          if (
            arrQid.find((qId) => parseInt(qId) === parseInt(e.id)) !==
            undefined
          ) {
            let findValue = tmp.sds30.options.find(
              (opt) => parseInt(opt.id) === parseInt(e.value)
            );
            if (findValue !== undefined) {
              k.score = k.score + parseInt(findValue.value);
            }
          }
        });

        tmp.sds30.interpretationRule.forEach((m) => {
          // debugger;
          if (m.isFix === "Y") {
            if (this.compareNumbers(k.score, parseInt(m.value), m.flag)) {
              k.level = m.id;
              k.levelDisplay = m.display;
            }
          } else if (m.isRange === "Y") {
            let min = parseInt(m.min);
            let max = parseInt(m.max);
            if (k.score >= min && k.score <= max) {
              k.level = m.id;
              k.levelDisplay = m.display;
            }
          }
        });
        console.log(
          `${k.display}, score : ${k.score}, level ${k.level}, ${k.levelDisplay}`
        );
      });
      this.data_sds = tmp;
    },
    interpretationSrq() {
      let tmp = this.data_sds;

      tmp.srq20.interpretation.forEach((k) => {
        var arrQid = k.questionID.split(",");
        k.score = 0;
        tmp.srq20.questions.forEach((e) => {
          if (
            arrQid.find((qId) => parseInt(qId) === parseInt(e.id)) !==
            undefined
          ) {
            if (
              tmp.srq20.options.find(
                (opt) =>
                  parseInt(opt.id) === parseInt(e.value) && opt.value === "Y"
              ) !== undefined
            ) {
              k.score = k.score + 1;
              console.log(`option dipilih ${e.value}`);
            }
            //   if (e.value === "Y") {
            //   }
          }
        });

        tmp.srq20.interpretationRule.forEach((m) => {
          // debugger;
          if (m.isFix === "Y") {
            if (this.compareNumbers(k.score, parseInt(m.value), m.flag)) {
              k.level = m.id;
              k.levelDisplay = m.display;
            }
          } else if (m.isRange === "Y") {
            let min = parseInt(m.min);
            let max = parseInt(m.max);
            if (k.score >= min && k.score <= max) {
              k.level = m.id;
              k.levelDisplay = m.display;
            }
          }
        });
        console.log(
          `${k.display}, score : ${k.score}, level ${k.level}, ${k.levelDisplay}`
        );
      });
      this.data_sds = tmp;
    },
    goToUp() {
      /*this.$nextTick(() => {
                this.$refs.stepbar_x.scrollTop = 0;
            });*/
      window.scrollTo({
        top: 0,
        left: 0,
        behavior: "smooth",
      });
    },
    nextStep(n) {
      console.log(n);
      if (n === this.tabs_fisik.length) {
        this.tab = 1;
      } else {
        this.tab = n + 1;
      }
    },
    backStep(n) {
      console.log(n);
      if (n === 1) {
        this.tab = 1;
      } else {
        this.tab = n - 1;
      }
    },
    saveSelectedTooth() {
      var fisiks = this.fisiks;
      var pos = this.tooth_pos;
      var icx = this.tooth_icx;
      var idx = this.tooth_idx;
      var tooth_value = this.kelainan_gigi;
      fisiks[icx].details[pos].details[idx].value = tooth_value.toUpperCase();
      console.log(fisiks[icx].details[pos].details[idx]);
      this.$store.commit("sample/update_fisiks", fisiks);
      this.dialog_choose_tooth = false;
    },
    openChooseToothDialog(position, tooth, icx) {
      this.kelainan_gigi = "";
      this.dialog_choose_tooth = true;
      var fisiks = this.fisiks;
      var pos = -1;
      if (position === "atasketiga") pos = 0;
      if (position === "ataskedua") pos = 1;
      if (position === "atas") pos = 2;
      if (position === "bawah") pos = 3;
      if (position === "bawahkedua") pos = 4;
      if (position === "bawahketiga") pos = 5;

      var idx = _.findIndex(fisiks[icx].details[pos].details, function (o) {
        return o.id_code == tooth.id_code;
      });
      this.tooth_pos = pos;
      this.tooth_icx = icx;
      this.tooth_idx = idx;
    },
    clear() {
      var _this = this;
      _this.sig.clear();
    },
    /*saveSignature() {
      var trx = this.$store.state.sample.selected_transaction;
      this.dialog_warning_signature = false;
      if (trx.image_signature === "") {
        this.save();
      } else {
        this.dialog_warning_signature = true;
      }
    },
    save(format) {
      this.dialog_warning_signature = false;
      var _this = this;
      //return format ? _this.sig.toDataURL(format) :  _this.sig.toDataURL()
      console.log(_this.sig.toDataURL()); // save image as PNG
      // signaturePad.toDataURL("image/jpeg"); // save image as JPEG
      // signaturePad.toDataURL("image/svg+xml"); // save image as SVG
      var prm = { data: _this.sig.toDataURL() };
      this.$store.dispatch("sample/save_signature", prm);
    },
    openDialog() {
      this.dialog_xsignature = true;
      this.$nextTick(() => {
        this.draw();
      });
    },
    draw() {
      var _this = this;
      var canvas = document.getElementById("signature_pad");
      //var canvas = document.querySelector("#signature_pad");
      _this.sig = new SignaturePad(canvas, {
        minWidth: 2,
        maxWidth: 4,
      });

      function resizeCanvas() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        let storedData = _this.sig.toData();
        _this.sig.clear(); // otherwise isEmpty() might return incorrect value
        _this.sig.fromData(storedData);
      }

      window.addEventListener("resize", resizeCanvas);
      resizeCanvas();
    },
    opendialogSignature() {
      console.log("waooaoao");
      this.$store.commit("sample/update_dialog_xsignature", true);
      this.$nextTick(() => {
        this.draw();
      });
    },*/
    checkXVSRiwayatCbx(id_code, event, idx_detail, idx_segment) {
      var riwayats = this.riwayats;
      var data = riwayats[idx_segment].details[idx_detail].details;
      if (riwayats[idx_segment].title === "RIWAYAT KEBIASAAN HIDUP") {
        data.forEach((value, key) => {
          if (value.id_code !== id_code) {
            data[key].chx = false;
            data[key].value = "";
          }
        });
        console.log("check");
        console.log(data);
        this.riwayats = riwayats;
      }
    },
    checkAnotherXCbx(id_code, event, idx_detail, idx_segment) {
      var riwayats = this.riwayats;
      var data = riwayats[idx_segment].details;
      data.forEach((value, key) => {
        if (value.id_code !== id_code) {
          data[key].chx = false;
          data[key].value = "";
        }
      });
      this.riwayats = riwayats;
    },
    checkAnotherCbx(id_code, event, idx_detail, idx_segment) {
      var riwayats = this.riwayats;
      var data = riwayats[idx_segment].details[idx_detail].details;
      //console.log(id_code)
      //console.log(event)
      //console.log(details)
      //console.log(data)
      data.forEach((value, key) => {
        if (value.id_code !== id_code) {
          data[key].chx = false;
          data[key].value = "";
        }
      });
      console.log("check");
      console.log(data);
      this.riwayats = riwayats;
    },
    doDownloadRpt() {
      var eurl = this.selected_download;
      var win = window.open(eurl, "_blank");
      win.focus();
    },
    downloadRpt(dx) {
      this.reports = dx.url;
      this.selected_download = "";
      if (dx.status_payment === "Y" && dx.url.length > 0) {
        if (dx.url.length === 1) {
          var eurl = dx.url[0].url;
          var win = window.open(eurl, "_blank");
          win.focus();
        } else {
          this.dialogdownload = true;
        }
      }
    },
    countBMI6mwt() {
      var data = this.$store.state.sample.data_6mwt;
      data.bmi = "";
      if (
        data.bb &&
        parseFloat(data.bb) > 0 &&
        data.tb &&
        parseFloat(data.tb) > 0
      ) {
        data.bmi =
          Math.round(
            (data.bb / ((data.tb / 100) * (data.tb / 100))).toFixed(2) * 10
          ) / 10;
      }
      this.$store.commit("sample/update_data_6mwt", data);
    },
    getnamelabel(kata) {
      var lang_id = this.$store.state.sample.sellang.id;
      var words = this.$store.state.sample.words;
      var row_word = _.filter(words, function (o) {
        return (
          o.Translate_WordNat_LangID === lang_id &&
          o.Translate_WordFrom.toLowerCase() === kata.toLowerCase()
        );
      });

      //console.log(row_word)
      if (row_word.length > 0) return row_word[0].Translate_WordTo;
      else return kata;
    },
    opendialogLang(test) {
      var xlng = { id: test.language_id, name: test.language_name };
      this.sellang = xlng;
      this.xnowtest = test;
      this.dialoglang = true;
    },
    getcolortensi(row) {
      console.log("new stage");
      console.log(row);
      var result = "grey--text";

      if (row.standart === "JNC-VII") {
        if (row.value === "NORMAL") {
          result = "info--text";
        }
        if (row.value === "PREHIPERTENSI") {
          result = "warning--text";
        }
        if (row.value === "HIPERTENSI STAGE 1") {
          result = "amber--text";
        }
        if (row.value === "HIPERTENSI STAGE 2") {
          result = "red--text";
        }
      }

      if (row.standart === "JNC-VIII") {
        if (row.value_x === "NORMAL") {
          result = "info--text";
        }
        if (row.value_x === "ELEVATED") {
          result = "warning--text";
        }
        if (row.value_x === "HIPERTENSI STAGE 1") {
          result = "amber--text";
        }
        if (row.value_x === "HIPERTENSI STAGE 2") {
          result = "red--text";
        }
        if (row.value_x === "HIPERTENSI STAGE 3") {
          result = "brown--text";
        }
      }

      if (row.standart === "ESC/ESH") {
        if (row.value_x === "OPTIMAL" || row.value_x === "NORMAL") {
          result = "info--text";
        }
        if (
          row.value_x === "HIGH NORMAL" ||
          row.value_x === "GRADE 1 HYPERTENSION"
        ) {
          result = "warning--text";
        }
        if (
          row.value_x === "HIGH NORMAL" ||
          row.value_x === "GRADE 1 HYPERTENSION"
        ) {
          result = "amber--text";
        }
        if (
          row.value_x === "GRADE 2 HYPERTENSION" ||
          row.value_x === "GRADE 3 HYPERTENSION"
        ) {
          result = "red--text";
        }
        if (row.value_x === "ISOLATED SYSTOLIC HYPERTENSION") {
          result = "brown--text";
        }
      }

      //console.log(result)
      return result;
    },
    changeStandartBMI(idx_f) {
      var fisiks = this.fisiks;
      var value_x = fisiks[idx_f].standart_bmi;
      var val_now = value_x;
      if (value_x === "who") {
        val_now = "asia_pacific";
      } else if (value_x === "asia_pacific") {
        val_now = "kemenkes";
      } else {
        val_now = "who";
      }
      //var val_now = fisiks[idx_f].standart_bmi === 'who' ? 'asia_pacific':'who'
      fisiks[idx_f].standart_bmi = val_now;
      // console.log('jalan')
      var idx_bmi = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == "status_gizi_4";
      });
      var bmi_value = fisiks[idx_f].details[idx_bmi].value;
      console.log("bmi : " + bmi_value);
      var classx = "-";
      if (
        bmi_value &&
        !isNaN(bmi_value) &&
        bmi_value > 0 &&
        bmi_value != "Infinity"
      ) {
        if (fisiks[idx_f].standart_bmi === "asia_pacific") {
          if (bmi_value < 18.5) classx = "Underweight";

          if (bmi_value >= 18.5 && bmi_value < 23) classx = "Normal";

          if (bmi_value >= 23 && bmi_value < 25) classx = "Overweight";

          if (bmi_value >= 25 && bmi_value < 30) classx = "Obese I";

          if (bmi_value >= 30) classx = "Obese II";
        }

        if (fisiks[idx_f].standart_bmi === "who") {
          //console.log(bmi_value)
          if (bmi_value < 18.5) classx = "Underweight";

          if (bmi_value >= 18.5 && bmi_value < 25) {
            classx = "Normal";
            // console.log("normal")
          }

          if (bmi_value >= 25 && bmi_value < 30) {
            classx = "Overweight";
            // console.log("iyyaaa")
          }

          if (bmi_value >= 30) classx = "Obese";
        }

        if (fisiks[idx_f].standart_bmi === "kemenkes") {
          if (bmi_value < 18.5) classx = "Underweight";

          if (bmi_value >= 18.5 && bmi_value < 25.1) classx = "Normal";

          if (bmi_value >= 25.1 && bmi_value < 27) classx = "Overweight";

          if (bmi_value >= 27) classx = "Obese";
        }
      }
      var idx_xclass = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == "status_gizi_6";
      });
      console.log(idx_xclass);
      fisiks[idx_f].details[idx_xclass].value = classx;
      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeXVSChx(idx_f, idx_s, row) {
      var fisiks = this.fisiks;
      var isnormal = row.is_normal;

      if (isnormal === "Y" && row.chx) {
        fisiks[idx_f].details[idx_s].details.forEach(function (entry, index) {
          if (fisiks[idx_f].details[idx_s].details[index].is_normal === "N") {
            fisiks[idx_f].details[idx_s].details[index].chx = false;
            fisiks[idx_f].details[idx_s].details[index].value = "";
          }
        });
        this.$store.commit("sample/update_fisiks", fisiks);
      }

      if (isnormal === "N" && row.chx) {
        var n_idx = _.findIndex(
          fisiks[idx_f].details[idx_s].details,
          function (o) {
            return o.is_normal === "Y";
          }
        );
        //console.log(n_idx)
        fisiks[idx_f].details[idx_s].details[n_idx].chx = false;
        fisiks[idx_f].details[idx_s].details[n_idx].value = "";
        this.$store.commit("sample/update_fisiks", fisiks);
      }
    },
    changeXVSChxLXX(idx_f, idx_s, row) {
      var fisiks = this.fisiks;
      var isnormal = row.is_normal;

      if (isnormal === "N" && !row.chx) {
        fisiks[idx_f].details[idx_s].details[1].details.forEach(function (
          entry,
          index
        ) {
          fisiks[idx_f].details[idx_s].details[1].details[
            index
          ].chx_a_value = false;
          fisiks[idx_f].details[idx_s].details[1].details[
            index
          ].chx_b_value = false;
        });
        this.$store.commit("sample/update_fisiks", fisiks);
      }

      if (isnormal === "N" && row.chx) {
        var n_idx = _.findIndex(
          fisiks[idx_f].details[idx_s].details,
          function (o) {
            return o.is_normal === "Y";
          }
        );
        //console.log(n_idx)
        fisiks[idx_f].details[idx_s].details[n_idx].chx = false;
        fisiks[idx_f].details[idx_s].details[n_idx].value = "";
        this.$store.commit("sample/update_fisiks", fisiks);
      }
    },
    changeXVChx(idx_f, row) {
      //console.log(idx_f)
      var fisiks = this.fisiks;
      var isnormal = row.is_normal;
      if (isnormal === "Y" && row.chx) {
        fisiks[idx_f].details.forEach(function (entry, index) {
          if (fisiks[idx_f].details[index].is_normal === "N") {
            fisiks[idx_f].details[index].chx = false;
            fisiks[idx_f].details[index].value = "";
          }
        });
        this.$store.commit("sample/update_fisiks", fisiks);
      }

      if (isnormal === "N" && row.chx) {
        var n_idx = _.findIndex(fisiks[idx_f].details, function (o) {
          return o.is_normal === "Y";
        });
        //console.log(n_idx)
        fisiks[idx_f].details[n_idx].chx = false;
        fisiks[idx_f].details[n_idx].value = "";
        this.$store.commit("sample/update_fisiks", fisiks);
      }
      //console.log("detailss")
      // console.log(fisiks[idx_f])
    },
    changeMXChx(idx_f, kdx_details, row) {
      console.log(idx_f)
      console.log(kdx_details)

      var fisiks = this.fisiks;
      if (row.chx) {
        fisiks[idx_f].details[kdx_details].details.forEach(function (entry, index) {
          if (fisiks[idx_f].details[kdx_details].details[index].id !== row.id) {
            fisiks[idx_f].details[kdx_details].details[index].chx = false;
          }
        });
        this.$store.commit("sample/update_fisiks", fisiks);
      }


    },
    changeToothValue(position, tooth, icx) {
      var fisiks = this.fisiks;
      var pos = -1;
      if (position === "atasketiga") pos = 0;
      if (position === "ataskedua") pos = 1;
      if (position === "atas") pos = 2;
      if (position === "bawah") pos = 3;
      if (position === "bawahkedua") pos = 4;
      if (position === "bawahketiga") pos = 5;

      var idx = _.findIndex(fisiks[icx].details[pos].details, function (o) {
        return o.id_code == tooth.id_code;
      });
      fisiks[icx].details[pos].details[idx].value = tooth.value.toUpperCase();
      console.log(fisiks[icx].details[pos].details[idx]);
      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeXXVChx(idx_f, row, status) {
      var fisiks = this.fisiks;

      var idx = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == row.id_code;
      });
      if (status == "y") {
        var now_y = fisiks[idx_f].details[idx].chx_y;
        fisiks[idx_f].details[idx].chx_x = now_y === true ? false : true;
        if (now_y) fisiks[idx_f].details[idx].disable_value = "Y";
        else fisiks[idx_f].details[idx].disable_value = "N";
      }
      if (status == "x") {
        var now_x = fisiks[idx_f].details[idx].chx_x;
        fisiks[idx_f].details[idx].chx_y = now_x === true ? false : true;
        if (now_x) fisiks[idx_f].details[idx].disable_value = "N";
        else fisiks[idx_f].details[idx].disable_value = "Y";
      }

      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeVXXChx(idx_f, row, status) {
      var fisiks = this.fisiks;
      //console.log(row)
      var idx = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == row.id_code;
      });

      if (status == "y") {
        var now_y = fisiks[idx_f].details[idx].chx_y;
        fisiks[idx_f].details[idx].chx_x = now_y ? false : true;
        fisiks[idx_f].details[idx].value = "";
      }

      if (status == "x") {
        var now_x = fisiks[idx_f].details[idx].chx_x;
        fisiks[idx_f].details[idx].chx_y = now_x ? false : true;
      }

      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeVXXValuePlusV(idx_f, row) {
      var fisiks = this.fisiks;
      //console.log(row)
      var idx = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == row.id_code;
      });

      var now_val = fisiks[idx_f].details[idx].value;
      if (now_val != "") {
        fisiks[idx_f].details[idx].chx_x = false;
      }

      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeVXXValue(idx_f, row) {
      console.log("new stage");
      var fisiks = this.fisiks;
      // console.log(row)
      if (row.table_name === "status_gizi") {
        // console.log(row)
        var idx_berat = _.findIndex(fisiks[idx_f].details, function (o) {
          return o.id_code == "status_gizi_1";
        });
        var val_berat = parseFloat(fisiks[idx_f].details[idx_berat].value);
        //console.log(val_berat)
        var idx_tinggi = _.findIndex(fisiks[idx_f].details, function (o) {
          return o.id_code == "status_gizi_2";
        });
        var val_tinggi = parseFloat(fisiks[idx_f].details[idx_tinggi].value);
        //console.log(val_tinggi)
        var idx_bmi = _.findIndex(fisiks[idx_f].details, function (o) {
          return o.id_code == "status_gizi_4";
        });
        var val_bmi = (
          val_berat /
          ((val_tinggi / 100) * (val_tinggi / 100))
        ).toFixed(2);
        console.log("bmi : " + val_bmi);
        fisiks[idx_f].details[idx_bmi].value = val_bmi;
        var bmi_value = val_bmi;
        var classx = "-";
        if (
          bmi_value &&
          !isNaN(bmi_value) &&
          bmi_value > 0 &&
          bmi_value != "Infinity"
        ) {
          if (fisiks[idx_f].standart_bmi === "asia_pacific") {
            if (bmi_value < 18.5) classx = "Underweight";

            if (bmi_value >= 18.5 && bmi_value < 23) classx = "Normal";

            if (bmi_value >= 23 && bmi_value < 25) classx = "Overweight";

            if (bmi_value >= 25 && bmi_value < 30) classx = "Obese I";

            if (bmi_value >= 30) classx = "Obese II";
          }

          if (fisiks[idx_f].standart_bmi === "who") {
            if (bmi_value < 18.5) classx = "Underweight";

            if (bmi_value >= 18.5 && bmi_value < 25) {
              classx = "Normal";
              // console.log("normal")
            }

            if (bmi_value >= 25 && bmi_value < 30) {
              classx = "Overweight";
              // console.log("iyyaaa")
            }

            if (bmi_value >= 30) classx = "Obese";
          }

          if (fisiks[idx_f].standart_bmi === "kemenkes") {
            if (bmi_value < 18.5) classx = "Underweight";

            if (bmi_value >= 18.5 && bmi_value < 25.1) classx = "Normal";

            if (bmi_value >= 25.1 && bmi_value < 27) classx = "Overweight";

            if (bmi_value >= 27) classx = "Obese";
          }
        }
        var idx_xclass = _.findIndex(fisiks[idx_f].details, function (o) {
          return o.id_code == "status_gizi_6";
        });
        fisiks[idx_f].details[idx_xclass].value = classx;
        this.$store.commit("sample/update_fisiks", fisiks);
      }
    },
    changeVXXValuePlus(idx_f, row) {
      console.log("new stage");
      var fisiks = this.fisiks;
      var idx_tensi = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == "tanda_vital_5";
      });
      var standart = fisiks[idx_f].details[idx_tensi].standart;
      console.log(standart);
      //Hipotensi itu kalau:
      // ⁠sistolik <= 99 dan atau diastolik <= 59
      if (row.table_name === "tanda_vital") {
        var result = fisiks[idx_f].details[idx_tensi].value_x;
        var xval = fisiks[idx_f].details[idx_tensi].value;
        if (xval !== "") {
          var arr_val = xval.split("/");
          var sistolik = arr_val[0];
          var diastolik = arr_val[1];
          if (standart === "JNC-VII") {
            if (sistolik <= 99 || diastolik <= 59) {
              result = "HIPOTENSI";
              console.log("IN");
            }
            if (
              sistolik < 120 &&
              sistolik > 99 &&
              diastolik < 80 &&
              diastolik > 59
            ) {
              console.log("kok?");
              result = "NORMAL";
            }
            if (
              (sistolik >= 120 && sistolik <= 139) ||
              (diastolik >= 80 && diastolik <= 89)
            ) {
              result = "PREHIPERTENSI";
            }
            if (
              (sistolik >= 140 && sistolik <= 159) ||
              (diastolik >= 90 && diastolik <= 99)
            ) {
              result = "HIPERTENSI STAGE 1";
            }
            if (sistolik >= 160 || diastolik >= 100) {
              result = "HIPERTENSI STAGE 2";
            }
          }
          if (standart === "JNC-VIII") {
            if (sistolik <= 99 || diastolik <= 59) {
              result = "HIPOTENSI";
            }
            if (
              sistolik < 120 &&
              sistolik > 99 &&
              diastolik < 80 &&
              diastolik > 59
            ) {
              result = "NORMAL";
            }
            if (sistolik >= 120 && sistolik <= 129 && diastolik < 80) {
              result = "ELEVATED";
            }
            if (
              (sistolik >= 130 && sistolik <= 139) ||
              (diastolik >= 80 && diastolik <= 89)
            ) {
              result = "HIPERTENSI STAGE 1";
            }
            if (sistolik >= 140 || diastolik >= 90) {
              result = "HIPERTENSI STAGE 2";
            }
            if (sistolik >= 180 || diastolik >= 120) {
              result = "HYPERTENSIVE CRISIS";
            }
          }
          if (standart === "ESC/ESH") {
            if (sistolik <= 99 || diastolik <= 59) {
              result = "HIPOTENSI";
            }
            if (
              sistolik < 120 &&
              sistolik > 99 &&
              diastolik < 80 &&
              diastolik > 59
            ) {
              result = "OPTIMAL";
            }
            if (
              (sistolik >= 120 && sistolik <= 129) ||
              (diastolik >= 80 && diastolik <= 84)
            ) {
              result = "NORMAL";
            }
            if (
              (sistolik >= 130 && sistolik <= 139) ||
              (diastolik >= 85 && diastolik <= 89)
            ) {
              result = "HIGH NORMAL";
            }
            if (
              (sistolik >= 140 && sistolik <= 159) ||
              (diastolik >= 90 && diastolik <= 99)
            ) {
              result = "GRADE 1 HYPERTENSION";
            }
            if (
              (sistolik >= 160 && sistolik <= 179) ||
              (diastolik >= 100 && diastolik <= 109)
            ) {
              result = "GRADE 2 HYPERTENSION";
            }
            if (sistolik >= 180 || diastolik >= 110) {
              result = "GRADE 3 HYPERTENSION";
            }
            if (sistolik >= 140 && diastolik < 90) {
              result = "ISOLATED SYSTOLIC HYPERTENSION";
            }
          }
        }
        fisiks[idx_f].details[idx_tensi].value_x = result;
        this.$store.commit("sample/update_fisiks", fisiks);
      }
    },
    changeVXXValuePlus2R(idx_f, row) {
      console.log("new stage");
      var fisiks = this.fisiks;
      var idx_tensi = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == "tanda_vital_5";
      });
      var standart = fisiks[idx_f].details[idx_tensi].standart;
      console.log(standart);
      if (row.table_name === "tanda_vital") {
        var result = fisiks[idx_f].details[idx_tensi].value;
        var sistolik = fisiks[idx_f].details[idx_tensi].value_x;
        var diastolik = fisiks[idx_f].details[idx_tensi].value_y;
        if (standart === "JNC-VII") {
          if (sistolik <= 99 || diastolik <= 59) {
            result = "HIPOTENSI";
            console.log("IN");
          }
          if (
            sistolik < 120 &&
            sistolik > 99 &&
            diastolik < 80 &&
            diastolik > 59
          ) {
            console.log("kok?");
            result = "NORMAL";
          }
          if (
            (sistolik >= 120 && sistolik <= 139) ||
            (diastolik >= 80 && diastolik <= 89)
          ) {
            result = "PREHIPERTENSI";
          }
          if (
            (sistolik >= 140 && sistolik <= 159) ||
            (diastolik >= 90 && diastolik <= 99)
          ) {
            result = "HIPERTENSI STAGE 1";
          }
          if (sistolik >= 160 || diastolik >= 100) {
            result = "HIPERTENSI STAGE 2";
          }
        }
        if (standart === "JNC-VIII") {
          if (sistolik <= 99 || diastolik <= 59) {
            result = "HIPOTENSI";
            console.log("IN");
          }
          if (
            sistolik < 120 &&
            sistolik > 99 &&
            diastolik < 80 &&
            diastolik > 59
          ) {
            console.log("kok?");
            result = "NORMAL";
          }
          if (sistolik >= 120 && sistolik <= 129 && diastolik < 80) {
            result = "ELEVATED";
          }
          if (
            (sistolik >= 130 && sistolik <= 139) ||
            (diastolik >= 80 && diastolik <= 89)
          ) {
            result = "HIPERTENSI STAGE 1";
          }
          if (sistolik >= 140 || diastolik >= 90) {
            result = "HIPERTENSI STAGE 2";
          }
          if (sistolik >= 180 || diastolik >= 120) {
            result = "HYPERTENSIVE CRISIS";
          }
        }
        if (standart === "ESC/ESH") {
          if (sistolik <= 99 || diastolik <= 59) {
            result = "HIPOTENSI";
            console.log("IN");
          }
          if (
            sistolik < 120 &&
            sistolik > 99 &&
            diastolik < 80 &&
            diastolik > 59
          ) {
            console.log("kok?");
            result = "OPTIMAL";
          }
          if (
            (sistolik >= 120 && sistolik <= 129) ||
            (diastolik >= 80 && diastolik <= 84)
          ) {
            result = "NORMAL";
          }
          if (
            (sistolik >= 130 && sistolik <= 139) ||
            (diastolik >= 85 && diastolik <= 89)
          ) {
            result = "HIGH NORMAL";
          }
          if (
            (sistolik >= 140 && sistolik <= 159) ||
            (diastolik >= 90 && diastolik <= 99)
          ) {
            result = "GRADE 1 HYPERTENSION";
          }
          if (
            (sistolik >= 160 && sistolik <= 179) ||
            (diastolik >= 100 && diastolik <= 109)
          ) {
            result = "GRADE 2 HYPERTENSION";
          }
          if (sistolik >= 180 || diastolik >= 110) {
            result = "GRADE 3 HYPERTENSION";
          }
          if (sistolik >= 140 && diastolik < 90) {
            result = "ISOLATED SYSTOLIC HYPERTENSION";
          }
        }

        fisiks[idx_f].details[idx_tensi].value = result;
        this.$store.commit("sample/update_fisiks", fisiks);
      }
    },
    changeStatusTooth(idx_f) {
      var fisiks = this.fisiks;
      //var idx_tensi = _.findIndex(fisiks[idx_f].details, function(o) { return o.id_code == 'tanda_vital_5'})
      //console.log("tooth-status")
      //console.log(fisiks[idx_f])
      fisiks[idx_f].is_normal = fisiks[idx_f].is_normal === "Y" ? "N" : "Y";
      if (fisiks[idx_f].is_normal === "Y") {
        fisiks[idx_f].details.forEach(function (value, idx) {
          let details_x = value.details;
          details_x.forEach(function (item, index) {
            console.log("detail_x");
            console.log(fisiks[idx_f].details[idx].details[index]);
            fisiks[idx_f].details[idx].details[index].value = "";
          });
        });
      }

      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeStatusInspected(idx_f) {
      var fisiks = this.fisiks;
      //var idx_tensi = _.findIndex(fisiks[idx_f].details, function(o) { return o.id_code == 'tanda_vital_5'})
      //console.log("tooth-status")
      //console.log(fisiks[idx_f])
      fisiks[idx_f].is_inspected =
        fisiks[idx_f].is_inspected === "Y" ? "N" : "Y";
      if (fisiks[idx_f].is_inspected === "Y") {
        fisiks[idx_f].details.forEach(function (value, idx) {
          let details_x = value.details;
          details_x.forEach(function (item, index) {
            console.log("detail_x");
            console.log(fisiks[idx_f].details[idx].details[index]);
            fisiks[idx_f].details[idx].details[index].value = "";
          });
        });
      }

      this.$store.commit("sample/update_fisiks", fisiks);
    },
    changeExistPajanan(idx_k) {
      var k3s = this.k3s;

      k3s[idx_k].is_notexist = k3s[idx_k].is_notexist === "Y" ? "N" : "Y";
      if (k3s[idx_k].is_notexist === "Y") {
        k3s[idx_k].details.forEach(function (value, idx) {
          k3s[idx_k].details[idx].chx = false;
          k3s[idx_k].details[idx].value_lama = "";
          k3s[idx_k].details[idx].value_sumber = "";
        });
      }

      this.$store.commit("sample/update_k3s", k3s);
    },
    changStandartTensi(idx_f, row) {
      console.log("new stage");
      var fisiks = this.fisiks;
      var idx_tensi = _.findIndex(fisiks[idx_f].details, function (o) {
        return o.id_code == "tanda_vital_5";
      });
      var standart = fisiks[idx_f].details[idx_tensi].standart; //=== 'JNC-VII' ? 'JNC-VIII':'JNC-VII'
      if (standart === "JNC-VII") {
        standart = "JNC-VIII";
        console.log(1);
      } else if (standart === "JNC-VIII") {
        standart = "ESC/ESH";
        console.log(2);
      } else {
        console.log(3);
        standart = "JNC-VII";
      }
      console.log(standart);
      fisiks[idx_f].details[idx_tensi].standart = standart;
      if (row.table_name === "tanda_vital") {
        var result = fisiks[idx_f].details[idx_tensi].value_x;
        var xval = fisiks[idx_f].details[idx_tensi].value;
        if (xval !== "") {
          var arr_val = xval.split("/");
          var sistolik = arr_val[0];
          var diastolik = arr_val[1];
          if (standart === "JNC-VII") {
            if (sistolik <= 99 || diastolik <= 59) {
              result = "HIPOTENSI";
              console.log("IN");
            }
            if (
              sistolik < 120 &&
              sistolik > 99 &&
              diastolik < 80 &&
              diastolik > 59
            ) {
              console.log("kok?");
              result = "NORMAL";
            }
            if (
              (sistolik >= 120 && sistolik <= 139) ||
              (diastolik >= 80 && diastolik <= 89)
            ) {
              result = "PREHIPERTENSI";
            }
            if (
              (sistolik >= 140 && sistolik <= 159) ||
              (diastolik >= 90 && diastolik <= 99)
            ) {
              result = "HIPERTENSI STAGE 1";
            }
            if (sistolik >= 160 || diastolik >= 100) {
              result = "HIPERTENSI STAGE 2";
            }
          }
          if (standart === "JNC-VIII") {
            if (sistolik < 120 && diastolik < 80) {
              result = "NORMAL";
            }
            if (sistolik >= 120 && sistolik <= 129 && diastolik < 80) {
              result = "ELEVATED";
            }
            if (
              (sistolik >= 130 && sistolik <= 139) ||
              (diastolik >= 80 && diastolik <= 89)
            ) {
              result = "HIPERTENSI STAGE 1";
            }
            if (sistolik >= 140 || diastolik >= 90) {
              result = "HIPERTENSI STAGE 2";
            }
            if (sistolik >= 180 || diastolik >= 120) {
              result = "HYPERTENSIVE CRISIS";
            }
          }
          if (standart === "ESC/ESH") {
            if (sistolik < 120 && diastolik < 80) {
              result = "OPTIMAL";
            }
            if (
              (sistolik >= 120 && sistolik <= 129) ||
              (diastolik >= 80 && diastolik <= 84)
            ) {
              result = "NORMAL";
            }
            if (
              (sistolik >= 130 && sistolik <= 139) ||
              (diastolik >= 85 && diastolik <= 89)
            ) {
              result = "HIGH NORMAL";
            }
            if (
              (sistolik >= 140 && sistolik <= 159) ||
              (diastolik >= 90 && diastolik <= 99)
            ) {
              result = "GRADE 1 HYPERTENSION";
            }
            if (
              (sistolik >= 160 && sistolik <= 179) ||
              (diastolik >= 100 && diastolik <= 109)
            ) {
              result = "GRADE 2 HYPERTENSION";
            }
            if (sistolik >= 180 || diastolik >= 110) {
              result = "GRADE 3 HYPERTENSION";
            }
            if (sistolik >= 140 && diastolik < 90) {
              result = "ISOLATED SYSTOLIC HYPERTENSION";
            }
          }
        }
        fisiks[idx_f].details[idx_tensi].value_x = result;
        this.$store.commit("sample/update_fisiks", fisiks);
      }
    },
    generate_rows(details) {
      //console.log(details)
      var riwayats = details;
      var row_riwayats = [];
      var rows = [];
      riwayats.forEach(function (item, index) {
        rows.push(item);
        if (index % 2 !== 0 || index === riwayats.length - 1) {
          row_riwayats.push(rows);
          rows = [];
        }
      });
      //console.log(row_riwayats)
      return row_riwayats;
    },
    changeFlagNormal(idx) {
      var riwayats = this.$store.state.sample.riwayats;
      var old_val = riwayats[idx].flag_normal;
      riwayats[idx].flag_normal = old_val === "Y" ? "N" : "Y";
      if (riwayats[idx].flag_normal === "Y") {
        riwayats[idx].details.forEach((item) => (item.chx = false));
        riwayats[idx].details.forEach((item) => (item.value = ""));
      }
      this.$store.commit("sample/update_riwayats", riwayats);
    },
    toggleDetailRiwayat(idx) {
      var riwayats = this.$store.state.sample.riwayats;
      var old_val = riwayats[idx].show_all;
      riwayats[idx].show_all = old_val === "Y" ? "N" : "Y";
      this.$store.commit("sample/update_riwayats", riwayats);
    },
    toggleDetailFisik(idx) {
      var fisiks = this.$store.state.sample.fisiks;
      var old_val = fisiks[idx].show_all;
      fisiks[idx].show_all = old_val === "Y" ? "N" : "Y";
      this.$store.commit("sample/update_fisiks", fisiks);
    },
    toggleDetailK3(idx) {
      var k3s = this.$store.state.sample.k3s;
      var old_val = k3s[idx].show_all;
      k3s[idx].show_all = old_val === "Y" ? "N" : "Y";
      this.$store.commit("sample/update_k3s", k3s);
    },
    selectedLang(value) {
      return { id: value.language_id, name: value.language_name };
    },
    selectedlangName(value) {
      return value.language_name;
    },
    check_saved(test) {
      var trx = this.$store.state.sample.selected_transaction;
      if (
        trx.details[0].template_name === "Fisik Umum" ||
        trx.details[0].template_name === "Fisik Umum K3" ||
        trx.details[0].template_name === "Fisik Umum Konsul"
      ) {
        return true;
      } else if (trx.details[0].template_name === "6MWT") {
        return this.$store.state.sample.data_6mwt.id === "0" ? false : true;
      } else {
        var arrdetails = test.details;
        var notempty = _.filter(arrdetails, function (o) {
          return o.result_value_before !== "";
        });
        //console.log(notempty)
        if (notempty.length === 0) return false;
        else return true;
      }
    },
    changeLang() {
      this.dialoglang = false;
      var trx = this.xnowtest;
      var arr = this.$store.state.sample.selected_transaction;
      var arrdetails = arr.details;
      let idx = _.findIndex(arrdetails, function (o) {
        return o.trx_id == trx.trx_id;
      });

      if (
        arr.details[0].template_name === "Fisik Umum" ||
        arr.details[0].template_name === "Fisik Umum K3" ||
        arr.details[0].template_name === "UMUM_KONSUL"
      ) {
        console.log(arr.details[0].template_name);
        if (arr.details[0].template_name === "Fisik Umum")
          this.$store.commit("sample/update_tabs_fisik", [
            { id: 1, name: "RIWAYAT" },
            { id: 2, name: "FISIK" },
          ]);
        if (arr.details[0].template_name === "Fisik Umum K3")
          this.$store.commit("sample/update_tabs_fisik", [
            { id: 1, name: "RIWAYAT" },
            { id: 2, name: "FISIK" },
            { id: 3, name: "PAJANAN" },
          ]);
        if (arr.details[0].template_name === "UMUM_KONSUL")
          this.$store.commit("sample/update_tabs_fisik", [
            { id: 1, name: "RIWAYAT" },
            { id: 2, name: "FISIK" },
            { id: 3, name: "KONSULTASI" },
          ]);
        var prm = arr.details[0];
        prm.act = arr.details[0].template_name;

        var seltrx = arr;
        seltrx.details[idx].language_id = this.$store.state.sample.sellang.id;
        seltrx.details[
          idx
        ].language_name = this.$store.state.sample.sellang.name;
        this.$store.commit("update_selected_transaction", seltrx);

        this.$store.dispatch("sample/getumum", prm);
        // this.generateRiwayatRow()
      } else {
        this.$store.dispatch("sample/getrstbylang", {
          selected_trx: arr,
          trx: trx,
          lang: this.$store.state.sample.sellang,
          selected_trx: this.$store.state.sample.selected_transaction,
          idx: idx,
          detail: arrdetails[idx],
        });
      }
    },
    selectLang(trx, lang) {
      var arr = this.$store.state.sample.selected_transaction;
      var arrdetails = arr.details;
      let idx = _.findIndex(arrdetails, function (o) {
        return o.trx_id == trx.trx_id;
      });

      if (
        arr.details[0].template_name === "Fisik Umum" ||
        arr.details[0].template_name === "Fisik Umum K3" ||
        arr.details[0].template_name === "UMUM_KONSUL"
      ) {
        console.log(arr.details[0].template_name);
        if (arr.details[0].template_name === "Fisik Umum")
          this.$store.commit("sample/update_tabs_fisik", [
            { id: 1, name: "RIWAYAT" },
            { id: 2, name: "FISIK" },
          ]);
        if (arr.details[0].template_name === "Fisik Umum K3")
          this.$store.commit("sample/update_tabs_fisik", [
            { id: 1, name: "RIWAYAT" },
            { id: 2, name: "FISIK" },
            { id: 3, name: "PAJANAN" },
          ]);
        if (arr.details[0].template_name === "UMUM_KONSUL")
          this.$store.commit("sample/update_tabs_fisik", [
            { id: 1, name: "RIWAYAT" },
            { id: 2, name: "FISIK" },
            { id: 3, name: "KONSULTASI" },
          ]);
        var prm = arr.details[0];
        prm.act = arr.details[0].template_name;

        var seltrx = arr;
        seltrx.details[idx].language_id = lang.id;
        seltrx.details[idx].language_name = lang.name;
        this.$store.commit("update_selected_transaction", seltrx);

        this.$store.dispatch("sample/getumum", prm);
        // this.generateRiwayatRow()
      } else {
        this.$store.dispatch("sample/getrstbylang", {
          selected_trx: arr,
          trx: trx,
          lang: lang,
          selected_trx: this.$store.state.sample.selected_transaction,
          idx: idx,
          detail: arrdetails[idx],
        });
      }
    },
    changeResult() {
      this.$store.commit("sample/update_no_save", 1);
    },
    changeFlagPrint(value, rst_idx, test_idx) {
      var selected_patient = this.$store.state.sample.selected_transaction;
      console.log(selected_patient);
      selected_patient.details[test_idx].details[rst_idx].flag_print =
        value === "Y" ? "N" : "Y";
      //console.log(selected_patient[test_idx][rst_idx])
      this.$store.dispatch("sample/save_flagprint", {
        selected_trx: selected_patient,
        row: selected_patient.details[test_idx].details[rst_idx],
      });
    },
    saveResult(trx, act) {
      var ar = this.$store.state.sample.transactions;
      var arr = this.$store.state.sample.selected_transaction;
      let idx = _.findIndex(ar, function (o) {
        return o.re_id == arr.re_id;
      });
      var template = arr.details[0].template_name;
      var error_msg = [];
      //var riwayats = this.riwayats
      if (
        template === "Fisik Umum" ||
        template === "Fisik Umum K3" ||
        template === "Fisik Umum Konsul"
      ) {
        var go_action = true;
        var riwayats = this.riwayats;
        var fisiks = this.fisiks;
        let k3s = this.k3s;
        var idx_keluhan = _.findIndex(riwayats, function (o) {
          return o.title == "KELUHAN SAAT INI";
        });
        riwayats.forEach(function (item, index) {
          console.log(item.flag_normal);
          if (item.flag_normal === "N") {
            var count_y = _.filter(item.details, function (o) {
              return o.chx;
            }).length;
            if (item.type_form === "XVS" || item.type_form === "XD") {
              count_y = 0;
              item.details.forEach(function (entry) {
                count_y += _.filter(entry.details, function (o) {
                  return o.chx;
                }).length;
              });
            }

            if (_.has(riwayats[idx_keluhan], "version")) {
              if (item.title === "RIWAYAT PENYAKIT KELUARGA") {
                if (riwayats[idx_keluhan].version === 2) {
                  count_y = 0;
                  item.details.forEach(function (entry) {
                    count_y += _.filter(entry.options, function (o) {
                      return o.selected;
                    }).length;
                  });
                }
              }
            }

            console.log(item);
            console.log(count_y);
            if (count_y === 0) {
              go_action = false;
              var x_msg = "<span style='color:red'>" + item.title + "</span>";

              error_msg.push(x_msg);
            }
          }
        });

        error_msg_fisik = [];
        fisiks.forEach(function (item, index) {
          if (item.title === "TANDA VITAL") {
            item.details.forEach(function (entry) {
              if (entry.id_code === "tanda_vital_5") {
                if (entry.value_x === "") {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.label_x + "</span>";
                  error_msg_fisik.push(x_msg);
                }
                if (entry.value_y === "") {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.label_y + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              } else if (entry.id_code === "tanda_vital_6") {
                if (entry.chx_x && entry.value === "") {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>Isi suhu jika demam</span>";
                  error_msg_fisik.push(x_msg);
                }
                if (!entry.chx_x && !entry.chx_y) {
                  console.log("suhu b");
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.label + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              } else if (
                entry.id_code === "tanda_vital_4" ||
                entry.id_code === "tanda_vital_2"
              ) {
                if (!entry.chx_x && !entry.chx_y) {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.label + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              } else {
                if (entry.value === "") {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.label + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              }

              //count_y += _.filter(entry.options, function(o) { return o.selected }).length
            });
          }

          var arr_form2 = ["KEADAAN UMUM"];
          if (arr_form2.includes(item.title)) {
            var x_count = 0;
            item.details.forEach(function (entry) {
              if (!entry.chx_x && !entry.chx_y) {
                go_action = false;
                var x_msg =
                  "<span style='color:red'>" + entry.label + "</span>";
                error_msg_fisik.push(x_msg);
              }
            });
          }

          if (item.title === "LAPANG PANDANG") {
            if (!item.details[0].chx_x && !item.details[0].chx_y) {
              go_action = false;
              var x_msg = "<span style='color:red'>" + item.title + "</span>";
              error_msg_fisik.push(x_msg);
            }
          }

          var arr_form3 = [
            "KEPALA WAJAH",
            "MATA",
            "TELINGA",
            "GIGI",
            "LEHER",
            "THORAX / DADA",
            "PARU-PARU",
            "GENITOURINARIA",
          ];
          if (arr_form3.includes(item.title)) {
            let count_x = _.filter(item.details, function (o) {
              return o.chx;
            }).length;
            if (count_x === 0) {
              go_action = false;
              var x_msg = "<span style='color:red'>" + item.title + "</span>";
              error_msg_fisik.push(x_msg);
            }
          }

          var arr_form4 = [
            "MULUT",
            "JANTUNG",
            "ANGGOTA GERAK",
            "SISTEM PERSYARAFAN",
            "SISTEM INTEGUMEN",
          ];
          if (arr_form4.includes(item.title)) {
            item.details.forEach((entry) => {
              let count_x = _.filter(entry.details, function (o) {
                return o.chx;
              }).length;
              if (count_x === 0) {
                go_action = false;
                var x_msg =
                  "<span style='color:red'>" + entry.name + "</span>";
                error_msg_fisik.push(x_msg);
              }
            });
          }

          var arr_form5 = ["SMELL TEST", "LOW BACK PAIN SCREENING TEST"];
          if (arr_form5.includes(item.title)) {
            if (item.is_inspected === "Y") {
              item.details.forEach((entry) => {
                let count_x = _.filter(entry.details, function (o) {
                  return o.chx;
                }).length;
                if (count_x === 0) {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.name + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              });
            }
          }

          if (item.title === "PERUT / ABDOMEN") {
            item.details.forEach((entry) => {
              if (entry.name !== "Hernia" && entry.name !== "Pengukuran") {
                let count_x = _.filter(entry.details, function (o) {
                  return o.chx;
                }).length;
                if (count_x === 0) {
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.name + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              }

              if (entry.name === "Hernia") {
                if (!entry.details[0].chx && !entry.details[1].chx) {
                  console.log("dua  hernia kiri");
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.name + "</span>";
                  error_msg_fisik.push(x_msg);
                }

                let child_details = entry.details[1].details;
                if (
                  entry.details[1].chx &&
                  !child_details[0].chx_value &&
                  !child_details[1].chx_a_value &&
                  !child_details[1].chx_b_value &&
                  !child_details[2].chx_a_value &&
                  !child_details[2].chx_b_value
                ) {
                  console.log("dua  hernia kanan");
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" + entry.name + "</span>";
                  error_msg_fisik.push(x_msg);
                }
              }

              if (entry.name === "Pengukuran") {
                if (entry.details[0].value == "") {
                  console.log("tiga " + entry.details[0].label);
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" +
                    entry.details[0].label +
                    "</span>";
                  error_msg_fisik.push(x_msg);
                }
                if (entry.details[1].value == "") {
                  console.log("tiga " + entry.details[1].label);
                  go_action = false;
                  var x_msg =
                    "<span style='color:red'>" +
                    entry.details[1].label +
                    "</span>";
                  error_msg_fisik.push(x_msg);
                }
              }
            });
          }
        });

        var error_msg_pajanan = [];
        if (k3s.length > 0) {
          k3s.forEach(function (item, index) {
            if (item.is_notexist === "N") {
              let count_x = _.filter(item.details, function (o) {
                return o.chx;
              }).length;
              if (count_x === 0) {
                go_action = false;
                var x_msg =
                  "<span style='color:red'>" + item.title + "</span>";
                error_msg_pajanan.push(x_msg);
              }
            }
          });
        }

        console.log("axx");
        console.log(go_action);
        if (go_action) {
          this.$store.commit("sample/update_last_id", arr.re_id);
          this.$store.dispatch("sample/savefisik", {
            template: template,
            startdate: this.$store.state.sample.start_date,
            enddate: this.$store.state.sample.end_date,
            search: this.$store.state.sample.name_lab,
            stationid: this.$store.state.sample.selected_station.id,
            groupid: this.$store.state.sample.select_item_group.id,
            subgroupid: this.$store.state.sample.select_item_subgroup.id,
            lastid: arr.re_id,
            trx: trx,
            trx_numbering: arr.ordernumber,
            riwayats: riwayats,
            fisiks: this.fisiks,
            umum_saran: this.umum_saran,
            konsul: this.konsul,
            k3s: this.k3s,
            act: template,
            action: act,
          });
        } else {
          console.log("in error new");
          console.log(error_msg);
          var msg = "";
          if (error_msg.length > 0) {
            msg +=
              "<p class='font-weigth-bold'>Mohon dicek ulang form riwayat berikut : </p>";
            msg += error_msg.join(", ");
          }

          if (error_msg_fisik.length > 0) {
            msg +=
              "<p class='mt-3 font-weigth-bold'>Mohon dicek ulang form fisik berikut : </p>";
            msg += error_msg_fisik.join(", ");
          }

          if (error_msg_pajanan.length > 0) {
            msg +=
              "<p class='mt-3 font-weigth-bold'>Mohon dicek ulang form pajanan berikut : </p>";
            msg += error_msg_pajanan.join(", ");
          }

          this.msg_alert_info = msg;
          this.dialog_alert_info = true;
        }
      } else if (template === "6MWT") {
        this.$store.dispatch("sample/save6mwt", {
          template: template,
          startdate: this.$store.state.sample.start_date,
          enddate: this.$store.state.sample.end_date,
          search: this.$store.state.sample.name_lab,
          stationid: this.$store.state.sample.selected_station.id,
          groupid: this.$store.state.sample.select_item_group.id,
          subgroupid: this.$store.state.sample.select_item_subgroup.id,
          lastid: arr.re_id,
          trx: trx,
          trx_numbering: arr.ordernumber,
          data_6mwt: this.$store.state.sample.data_6mwt,
          vomax: this.textvomax,
          category: this.categoryvomax,
          act: template,
          action: act,
        });
      } else if (template === "SDS") {
        this.interpretationSds();
        this.interpretationSrq();
        if (
          this.data_sds.identitas.jenis_kelamin.trim() === "" ||
          this.data_sds.identitas.status_pekerja.trim() === "" ||
          this.data_sds.identitas.jenis_pekerjaan.trim() === "" ||
          this.data_sds.identitas.level_jabatan.trim() === "" ||
          this.data_sds.identitas.status_perkawinan.trim() === "" ||
          this.data_sds.identitas.pendidikan.trim() === ""
        ) {
          var msg = "Isi identitas bertanda *";
          this.$store.commit("sample/update_msg_info", msg);
          this.$store.commit("sample/update_open_dialog_info", true);
          return;
        }

        let prm = {
          template: template,
          startdate: this.$store.state.sample.start_date,
          enddate: this.$store.state.sample.end_date,
          search: this.$store.state.sample.name_lab,
          stationid: this.$store.state.sample.selected_station.id,
          groupid: this.$store.state.sample.select_item_group.id,
          subgroupid: this.$store.state.sample.select_item_subgroup.id,
          lastid: arr.re_id,
          trx: trx,
          trx_numbering: arr.ordernumber,
          data_sds: this.$store.state.sample.data_sds,
          vomax: this.textvomax,
          category: this.categoryvomax,
          act: template,
          action: act,
        };
        this.$store.dispatch("sample/savesds", prm);
      } else {
        this.$store.dispatch("sample/saveresult", {
          startdate: this.$store.state.sample.start_date,
          enddate: this.$store.state.sample.end_date,
          search: this.$store.state.sample.name_lab,
          stationid: this.$store.state.sample.selected_station.id,
          groupid: this.$store.state.sample.select_item_group.id,
          subgroupid: this.$store.state.sample.select_item_subgroup.id,
          lastid: arr.re_id,
          trx: trx,
          trx_numbering: arr.ordernumber,
          act: act,
        });
      }
    },
    val1(trx, act) {
      //console.log(act)
      if (parseInt(trx.doctor_id) === 0) {
        var msg =
          "Orang sakit diperiksa dokter, kalau kosong tiada yang periksa";
        this.$store.commit("sample/update_msg_info", msg);
        this.$store.commit("sample/update_open_dialog_info", true);
      } else {
        this.$store.commit("sample/update_act", act);
        this.$store.commit("sample/update_last_trx", trx);
        var arr = this.$store.state.sample.selected_transaction;
        var msg =
          "Anda yakin akan melakukan verifikasi untuk pemeriksaan " +
          trx.test_name +
          " dari pasien " +
          arr.patient_fullname +
          " ?";
        this.$store.commit("sample/update_msg_action", msg);
        this.$store.commit("sample/update_dialog_action", true);
      }
    },
    unval1(trx, act) {
      console.log(act);
      this.$store.commit("sample/update_act", act);
      this.$store.commit("sample/update_last_trx", trx);
      var arr = this.$store.state.sample.selected_transaction;
      var msg =
        "Anda yakin akan membatalkan verifikasi untuk pemeriksaan " +
        trx.test_name +
        " dari pasien " +
        arr.patient_fullname +
        " ?";
      this.$store.commit("sample/update_msg_action", msg);
      this.$store.commit("sample/update_dialog_action", true);
    },
    val2(trx, act) {
      console.log(act);
      this.$store.commit("sample/update_act", act);
      this.$store.commit("sample/update_last_trx", trx);
      var arr = this.$store.state.sample.selected_transaction;
      var msg =
        "Anda yakin akan melakukan validasi untuk pemeriksaan " +
        trx.test_name +
        " dari pasien " +
        arr.patient_fullname +
        " ?";
      this.$store.commit("sample/update_msg_action", msg);
      this.$store.commit("sample/update_dialog_action", true);
    },
    unval2(trx, act) {
      console.log(act);
      this.$store.commit("sample/update_act", act);
      this.$store.commit("sample/update_last_trx", trx);
      var arr = this.$store.state.sample.selected_transaction;
      var msg =
        "Anda yakin akan membatalkan validasi untuk pemeriksaan " +
        trx.test_name +
        " dari pasien " +
        arr.patient_fullname +
        " ?";
      this.$store.commit("sample/update_msg_action", msg);
      this.$store.commit("sample/update_dialog_action", true);
    },
    lama_print(trx, act) {
      console.log(act);
      this.$store.commit("sample/update_act", act);
      this.$store.commit("sample/update_last_trx", trx);
      this.printwidth = 1028;
      this.printtitle = "";
      let idx = trx.trx_id;
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      var rptname = "rpt_hasil_so";
      if (trx.language_name === "EN") rptname = "rpt_hasil_so_eng";

      this.urlprint =
        "/birt/run?__report=report/one/lab/" +
        rptname +
        ".rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PID=" +
        idx +
        "&PLang=" +
        trx.language_id +
        "&tm=" +
        n;

      this.openprint = true;
    },
    async doPrintAfterLang() {
      this.dialogprintlang = false;
      var trx = this.$store.state.sample.selected_test;
      var trx_selected = this.$store.state.sample.selected_transaction;
      console.log(trx);
      var idx_true = _.findIndex(
        this.dialoglangs,
        (item) => item.chex === "Y"
      );
      this.xselectedlang = { id: 1, name: "Bahasa Indonesia" };
      this.printwidth = 1028;
      this.printtitle = "";
      let idx = trx.re_id;
      let user = one_user();
      var d = new Date();
      var n = d.getTime();
      var rptname = "";
      var folder = "lab";
      var rptname = "rpt_hasil_so_layanan";
      if (this.xselectedlang.id == 2) {
        rptname = "rpt_hasil_so_layanan_eng";
      }
      var rptname_email = "rpt_hasil_so_layanan_email";
      if (trx.test_name.toUpperCase() === "MANTHOUX TEST")
        rptname = "rpt_hasil_so_manthoux";

      if (this.xselectedlang.id == 2) {
        rptname = "rpt_hasil_so_layanan_eng";
        if (trx.test_name.toUpperCase() === "MANTHOUX TEST")
          rptname = "rpt_hasil_so_manthoux_eng";
      }

      rptname_email = rptname + "_email";

      var xurl = "";
      var xurl_email = "";
      var template = trx_selected.details[0].template_name;
      this.urlprint = "";
      if (template === "Fisik Umum" || template === "Fisik Umum K3") {
        folder = "mcu";
        var typemcu = "riwayat";
        var rpt_emails = [];
        var riwayats = this.riwayats;
        var idx_keluhan = _.findIndex(riwayats, function (o) {
          return o.title == "KELUHAN SAAT INI";
        });

        if (this.tab === 1 && this.xselectedlang.id !== 2) {
          rptname = "mcu_riwayat";
          typemcu = "riwayat";
          if (_.has(riwayats[idx_keluhan], "version")) {
            if (riwayats[idx_keluhan].version === 2) {
              rptname = "mcu_riwayat";
            }
          }
          //rpt_emails.push({rptname:rptname+"_email",typemcu:typemcu})
        }
        if (this.tab === 1 && this.xselectedlang.id == 2) {
          rptname = "mcu_riwayat_eng";
          typemcu = "riwayat";
          //rpt_emails.push({rptname:rptname+"_email",typemcu:typemcu})
          if (_.has(riwayats[idx_keluhan], "version")) {
            if (riwayats[idx_keluhan].version === 2) {
              rptname = "mcu_riwayat_eng_v2";
            }
          }
        }
        if (this.tab == 2 && this.xselectedlang.id !== 2) {
          rptname = "mcu_fisik";
          typemcu = "fisik";
          // rpt_emails.push({rptname:rptname+"_email",typemcu:typemcu})
          if (_.has(riwayats[idx_keluhan], "version")) {
            if (riwayats[idx_keluhan].version === 2) {
              rptname = "mcu_fisik";
            }
          }
        }
        if (this.tab == 2 && this.xselectedlang.id == 2) {
          rptname = "mcu_fisik_eng";
          typemcu = "fisik";
          // rpt_emails.push({rptname:rptname,typemcu:typemcu})
          if (_.has(riwayats[idx_keluhan], "version")) {
            if (riwayats[idx_keluhan].version === 2) {
              rptname = "mcu_fisik_eng_v2";
            }
          }
        }
        if (this.tab == 3 && this.xselectedlang.id !== 2) {
          rptname = "mcu_pajanan";
          typemcu = "k3";
          /*if(_.has(riwayats[idx_keluhan], 'version')){
                        if(riwayats[idx_keluhan].version === 2){
                            rptname = "mcu_pajanan_v2"
                        }
                    }*/
        }
        if (this.tab == 3 && this.xselectedlang.id == 2) {
          rptname = "mcu_pajanan_eng";
          typemcu = "k3";
          /*if(_.has(riwayats[idx_keluhan], 'version')){
                        if(riwayats[idx_keluhan].version === 2){
                            rptname = "mcu_pajanan_eng_v2"
                        }
                    }*/
        }
        //console.log(rptname);
        rptname_email = rptname + "_email";

        xurl =
          "/birt/run?__report=report/one/mcu/" +
          rptname +
          ".rptdesign&__format=pdf&PID=" +
          idx +
          "&PLang=" +
          trx.language_id +
          "&PType=" +
          typemcu +
          "&username=" +
          user.M_StaffName +
          "&tm=" +
          n;
        if (template === "Fisik Umum" || "UMUM_KONSUL") {
          xurl_email =
            "/birt/run?__report=report/one/mcu/mcu_riwayat_email.rptdesign&__format=pdf&PID=" +
            idx +
            "&PLang=" +
            trx.language_id +
            "&PType=riwayat&username=" +
            user.M_StaffName +
            "&tm=" +
            n;
          xurl_email += "|^|";
          xurl_email +=
            "/birt/run?__report=report/one/mcu/mcu_fisik_email.rptdesign&__format=pdf&PID=" +
            idx +
            "&PLang=" +
            trx.language_id +
            "&PType=fisik&username=" +
            user.M_StaffName +
            "&tm=" +
            n;
          if (_.has(riwayats[idx_keluhan], "version")) {
            if (riwayats[idx_keluhan].version === 2) {
              xurl_email =
                "/birt/run?__report=report/one/mcu/mcu_riwayat_v2_email.rptdesign&__format=pdf&PID=" +
                idx +
                "&PLang=" +
                trx.language_id +
                "&PType=riwayat&username=" +
                user.M_StaffName +
                "&tm=" +
                n;
              xurl_email += "|^|";
              xurl_email +=
                "/birt/run?__report=report/one/mcu/mcu_fisik_v2_email.rptdesign&__format=pdf&PID=" +
                idx +
                "&PLang=" +
                trx.language_id +
                "&PType=fisik&username=" +
                user.M_StaffName +
                "&tm=" +
                n;
            }
          }
        }

        if (template === "Fisik Umum K3") {
          xurl_email =
            "/birt/run?__report=report/one/mcu/mcu_riwayat_email.rptdesign&__format=pdf&PID=" +
            idx +
            "&PLang=" +
            trx.language_id +
            "&PType=riwayat&username=" +
            user.M_StaffName +
            "&tm=" +
            n;
          xurl_email += "|^|";
          xurl_email +=
            "/birt/run?__report=report/one/mcu/mcu_fisik_email.rptdesign&__format=pdf&PID=" +
            idx +
            "&PLang=" +
            trx.language_id +
            "&PType=fisik&username=" +
            user.M_StaffName +
            "&tm=" +
            n;
          xurl_email += "|^|";
          xurl_email +=
            "/birt/run?__report=report/one/mcu/mcu_pajanan_email.rptdesign&__format=pdf&PID=" +
            idx +
            "&PLang=" +
            trx.language_id +
            "&PType=pajanan&username=" +
            user.M_StaffName +
            "&tm=" +
            n;
          if (_.has(riwayats[idx_keluhan], "version")) {
            if (riwayats[idx_keluhan].version === 2) {
              xurl_email =
                "/birt/run?__report=report/one/mcu/mcu_riwayat_v2_email.rptdesign&__format=pdf&PID=" +
                idx +
                "&PLang=" +
                trx.language_id +
                "&PType=riwayat&username=" +
                user.M_StaffName +
                "&tm=" +
                n;
              xurl_email += "|^|";
              xurl_email +=
                "/birt/run?__report=report/one/mcu/mcu_fisik_v2_email.rptdesign&__format=pdf&PID=" +
                idx +
                "&PLang=" +
                trx.language_id +
                "&PType=fisik&username=" +
                user.M_StaffName +
                "&tm=" +
                n;
              xurl_email += "|^|";
              xurl_email +=
                "/birt/run?__report=report/one/mcu/mcu_pajanan_email.rptdesign&__format=pdf&PID=" +
                idx +
                "&PLang=" +
                trx.language_id +
                "&PType=pajanan&username=" +
                user.M_StaffName +
                "&tm=" +
                n;
            }
          }
        }
      } else if (template === "6MWT") {
        rptname = "rpt_hasil_so_smwt";
        rptname_email = rptname + "_email";

        xurl =
          "/birt/run?__report=report/one/" +
          folder +
          "/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          idx +
          "&PLang=" +
          trx.language_id +
          "&tm=" +
          n;
        xurl_email =
          "/birt/run?__report=report/one/" +
          folder +
          "/" +
          rptname_email +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          idx +
          "&PLang=" +
          trx.language_id +
          "&tm=" +
          n;
      } else if (template === "SDS") {
        let sds_type = this.type_sds_form;
        let rpt_type = sds_type === "SDS30" ? "30" : "20";

        rptname = "rpt_hasil_so_sds";
        rptname_email = rptname + "_email";

        xurl =
          "/birt/run?__report=report/one/" +
          folder +
          "/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          idx +
          "&PType=" +
          rpt_type +
          "&PLang=" +
          trx.language_id +
          "&tm=" +
          n;
        xurl_email =
          "/birt/run?__report=report/one/" +
          folder +
          "/" +
          rptname_email +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          idx +
          "&PLang=" +
          trx.language_id +
          "&tm=" +
          n;
      } else {
        xurl =
          "/birt/run?__report=report/one/" +
          folder +
          "/" +
          rptname +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          idx +
          "&PLang=" +
          trx.language_id +
          "&tm=" +
          n;

        xurl_email =
          "/birt/run?__report=report/one/" +
          folder +
          "/" +
          rptname_email +
          ".rptdesign&__format=pdf&username=" +
          user.M_StaffName +
          "&PID=" +
          idx +
          "&PLang=" +
          trx.language_id +
          "&tm=" +
          n;
      }
      console.log("ini mulai url email");
      console.log(template);
      console.log(xurl_email);
      this.urlprint = xurl;
      let prm_email = {
        orderID: trx.orderid,
        type: trx.group_name,
        url: xurl_email,
        re_id: trx.re_id,
        format: "",
      };
      //await this.$store.dispatch("email/save",prm_email)
      console.log(xurl);
      this.openprint = true;
      var seltrx = this.$store.state.sample.selected_transaction;
      var deliveries = seltrx.deliveries;
      var e_del = _.filter(deliveries, function (o) {
        return (
          o.code === "EMAIL" || o.code === "WHATSAPP" || o.code === "TELEGRAM"
        );
      });
      console.log(e_del);
      if (e_del.length > 0) {
        this.$store.dispatch("email/save", prm_email);
      }
    },
    print1tahun() {
      var trx = this.$store.state.sample.selected_test;
      var trx_selected = this.$store.state.sample.selected_transaction
        .details[0];
      console.log(trx);
      var idx_true = _.findIndex(
        this.dialoglangs,
        (item) => item.chex === "Y"
      );
      this.xselectedlang = { id: 1, name: "Bahasa Indonesia" };
      this.printwidth = 1028;
      this.printtitle = "";
      let idx = trx_selected.re_id;
      let user = one_user();
      var xdate = Date.now();
      // var xurl = "/birt/run?__report=report/one/mcu/mcu_fisik_history.rptdesign&__format=pdf&PID=545&PLang=1&PType=fisik&username=PETUGAS%20SAMPLE%20LAB&tm=1721356633262
      let xurl =
        "/birt/run?__report=report/one/mcu/mcu_fisik_history_v2.rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PType=fisik&PID=" +
        idx +
        "&PLang=1&tm=" +
        xdate;
      this.urlprint = xurl;
      this.openprint = true;
    },
    print2tahun() {
      var trx = this.$store.state.sample.selected_test;
      var trx_selected = this.$store.state.sample.selected_transaction
        .details[0];
      console.log(trx);
      var idx_true = _.findIndex(
        this.dialoglangs,
        (item) => item.chex === "Y"
      );
      this.xselectedlang = { id: 1, name: "Bahasa Indonesia" };
      this.printwidth = 1028;
      this.printtitle = "";
      let idx = trx_selected.re_id;
      let user = one_user();
      var xdate = Date.now();
      // var xurl = "/birt/run?__report=report/one/mcu/mcu_fisik_history.rptdesign&__format=pdf&PID=545&PLang=1&PType=fisik&username=PETUGAS%20SAMPLE%20LAB&tm=1721356633262
      let xurl =
        "/birt/run?__report=report/one/mcu/mcu_fisik_history.rptdesign&__format=pdf&username=" +
        user.M_StaffName +
        "&PType=fisik&PID=" +
        idx +
        "&PLang=1&tm=" +
        xdate;
      this.urlprint = xurl;
      this.openprint = true;
    },
    print(trx, act, template_xname) {
      this.template_xname = template_xname;
      // console.log(trx)
      //this.dialogprintlang = true
      var nowlang = trx.langs;
      console.log(trx);
      //_.forEach(nowlang, function(num) { num.chex = 'N' })
      //var lang_idx = _.findIndex(nowlang, function(o) { return o.id == trx.language_id })
      //nowlang[lang_idx].chex = 'Y'
      //this.$store.commit("sample/update_item_langs",nowlang )
      //this.xselectedlang = trx.langs[0]
      this.$store.commit("sample/update_selected_test", trx);
      this.$store.commit("sample/update_act", act);
      this.$store.commit("sample/update_last_trx", trx);
      this.doPrintAfterLang();
    },
    closePrint() {
      this.openprint = false;
      var prm = this.$store.state.sample.last_trx;
      this.$store.dispatch("sample/printcount", prm);
    },
    savedoctor() {
      var prm = {};
      prm.selected_detail = this.$store.state.sample.selected_detail;
      prm.selected_doctor = this.selected_doctor;
      this.$store.dispatch("sample/savedoctor", prm);
    },
    closeDialogAction() {
      var trx = this.$store.state.sample.last_trx;
      var act = this.$store.state.sample.act;
      this.saveResult(trx, act);
    },
    opendialogdoctor(test) {
      this.$store.commit("sample/update_selected_detail", test);
      this.$store.commit("sample/update_doctors", []);
      this.selected_doctor = {};
      if (test.doctor_id !== 0 || test.doctor_id !== "0") {
        this.selected_doctor = {
          id: test.doctor_id,
          name: test.doctor_fullname,
        };
        this.$store.commit("sample/update_doctors", [
          { id: test.doctor_id, name: test.doctor_fullname },
        ]);
      }
      this.xdialogdoctor = true;
    },
    openImage(value) {
      this.$store.commit("sample/update_selected_photo", value);
      this.$store.commit("sample/update_image", value.newname);
      this.$store.commit("sample/update_dialog_image", true);
    },
    pasteTemplate(rst, idx) {
      this.idx_test = idx;
      var seltrx = this.$store.state.sample.selected_transaction;
      var datax = seltrx.details[idx];
      if (datax.status === "NEW") {
        var prm = {
          idx: idx,
          template_id: datax.template_id,
          doctor_id: datax.doctor_id,
          language_id: datax.language_id,
          test_id: datax.test_id,
        };
        this.$store.dispatch("sample/gettemplate", prm);
      }
    },
    doPasteTemplate() {
      var seltrx = this.$store.state.sample.selected_transaction;
      //console.log(seltrx)
      var temp_val = this.$store.state.sample.selected_template.details;
      var test_idx = this.idx_test;

      seltrx.details[test_idx].details.forEach(function (temp, index) {
        var template_detail_id =
          seltrx.details[test_idx].details[index].template_detail_id;
        var value_now = seltrx.details[test_idx].details[index].result_value;
        var filter_temp = _.filter(temp_val, function (o) {
          return o.template_detail_id === template_detail_id;
        });
        //console.log(filter_temp)
        if (filter_temp.length > 0 && (!value_now || value_now === "")) {
          seltrx.details[test_idx].details[index].result_value =
            filter_temp[0].value;
        }
      });

      this.$store.commit("update_selected_transaction", seltrx);
      this.dialogtemplates = false;
    },
    thr_search_doctor: _.debounce(function () {
      this.$store.dispatch("sample/searchdoctor", this.search_doctor);
    }, 2000),
  },
  watch: {
    search_doctor(val, old) {
      if (val == old) return;
      if (!val) return;
      if (val.length < 1) return;
      //if (this.$store.state.sample.update_autocomplete_status == 1 ) return
      this.thr_search_doctor();
    },
  },
};
</script>
