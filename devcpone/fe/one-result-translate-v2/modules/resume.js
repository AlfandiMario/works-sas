// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/resume.js"

export default {
    namespaced: true,
    state: {
        lookup_status: 0,
        loading: false,
        setupList: [],
        selectedSetup: {},
        startDate: moment(new Date()).format('YYYY-MM-DD'),
        endDate: moment(new Date()).format('YYYY-MM-DD'),
        search: '',
        errorMsg: '',
        snackbarSuccess: false,
        snackbarError: false,
        successMsg: '',
        patientList: [],
        selectedPatient: {},
        totalPage: 0,
        page: 1,
        patientDetail: {
            'lab': [],
            'nonlab': [],
            'fisik': [],
        },
        rekomendasi: '',
        saran: '',
        kesimpulan: '',
        doctorList: [],
        selectedDoctor: {},
        searchDoctor: '',
        dialogDoctor: false,
        loadingSave: false,
        loadingDetail: false,
        fitnessCategory: [],
        selectedFitnessCategory: 0,
        dialogFitness: false,
        dataFitness: {
            "status": {
                "name": "",
                "name_eng": "",
                "level": ""
            },
            "data": []
        },
    },
    mutations: {
        update_dialogFitness(state, val) {
            state.dialogFitness = val
        },
        update_dataFitness(state, val) {
            state.dataFitness = val
        },
        update_selectedFitnessCategory(state, val) {
            state.selectedFitnessCategory = val
        },
        update_fitnessCategory(state, val) {
            state.fitnessCategory = val
        },
        update_lookup_status(state, val) {
            state.lookup_status = val
        },
        update_loading(state, val) {
            state.loading = val
        },
        update_setupList(state, val) {
            state.setupList = val
        },
        update_selectedSetup(state, val) {
            state.selectedSetup = val
        },
        update_startDate(state, val) {
            state.startDate = val
        },
        update_endDate(state, val) {
            state.endDate = val
        },
        update_search(state, val) {
            state.search = val
        },
        update_errorMsg(state, val) {
            state.errorMsg = val
        },
        update_snackbarSuccess(state, val) {
            state.snackbarSuccess = val
        },
        update_snackbarError(state, val) {
            state.snackbarError = val
        },
        update_successMsg(state, val) {
            state.successMsg = val
        },
        update_patientList(state, val) {
            state.patientList = val
        },
        update_selectedPatient(state, val) {
            state.selectedPatient = val
        },
        update_totalPage(state, val) {
            state.totalPage = val
        },
        update_page(state, val) {
            state.page = val
        },
        update_patientDetail(state, val) {
            state.patientDetail = val
        },
        update_rekomendasi(state, val) {
            state.rekomendasi = val
        },
        update_kesimpulan(state, val) {
            state.kesimpulan = val
        },
        update_saran(state, val) {
            state.saran = val
        },
        reset_input(state) {
            state.kesimpulan = '';
            state.rekomendasi = '';
            state.saran = '';
        },
        update_doctorList(state, val) {
            state.doctorList = val
        },
        update_selectedDoctor(state, val) {
            state.selectedDoctor = val
        },
        update_searchDoctor(state, val) {
            state.searchDoctor = val
        },
        update_dialogDoctor(state, val) {
            state.dialogDoctor = val
        },
        update_loadingSave(state, val) {
            state.loadingSave = val
        },
        update_loadingDetail(state, val) {
            state.loadingDetail = val
        },
    },
    actions: {
        async getsetup(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                }
                let resp = await api.getsetup(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)

                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_setupList", resp.data.records)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async search(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    search: context.state.search,
                    page: context.state.page,
                    startDate: context.state.startDate,
                    endDate: context.state.endDate,
                    setupID: context.state.selectedSetup.Mgm_McuID
                }
                let resp = await api.search(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)

                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_patientList", resp.data.records)
                    context.commit("update_totalPage", resp.data.total)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async getdetail(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loadingDetail", true)
            try {
                let prm = {
                    token: one_token(),
                    orderid: context.state.selectedPatient.orderID,
                    lang: 2
                }
                let resp = await api.getdetail(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loadingDetail", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {


                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    context.commit("update_patientDetail", resp.data);
                    // context.commit("update_selectedFitnessCategory", resp.data.header.fitnessCategory);

                    // if (resp.data.records.length > 0) {
                    //     context.commit("update_rekomendasi", resp.data.records[0].resumeRekomendasi)
                    //     context.commit("update_kesimpulan", resp.data.records[0].resumeKesimpulan)
                    //     context.commit("update_saran", resp.data.records[0].resumeSaran)
                    // }
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loadingDetail", false)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loadingDetail", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async getdoctorlist(context, prm) {
            context.commit("update_lookup_status", 1)
            // context.commit("update_loading", true)
            try {
                prm.token = one_token()
                let resp = await api.getdoctor(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)

                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_doctorList", resp.data.records)

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", false)

            }
        },
        async getFitnessCategory(context) {
            context.commit("update_lookup_status", 1)
            // context.commit("update_loading", true)
            try {
                let prm = {};
                prm.token = one_token()
                let resp = await api.getFitnessCategory(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_loading", false)

                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_fitnessCategory", resp.data.records)

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", false)

            }
        },
        async save(context, prm) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                prm.orderid = context.state.selectedPatient.orderID;
                let resp = await api.save(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loadingSave", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    await context.dispatch("search")
                    await context.dispatch("getdetail")
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_successMsg", 'Berhasil Simpan Data')
                    context.commit("update_loadingSave", false)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loadingSave", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async saveNonlab(context, prm) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                prm.orderid = context.state.selectedPatient.orderID;
                let resp = await api.saveNonlab(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loadingSave", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    await context.dispatch("search")
                    await context.dispatch("getdetail")
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_successMsg", 'Berhasil Simpan Data')
                    context.commit("update_loadingSave", false)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loadingSave", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async saveFisikUmum(context, prm) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                prm.orderid = context.state.selectedPatient.orderID;
                let resp = await api.saveFisikUmum(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loadingSave", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    await context.dispatch("search")
                    await context.dispatch("getdetail")
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_successMsg", 'Berhasil Simpan Data')
                    context.commit("update_loadingSave", false)
                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loadingSave", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async savedoctor(context, prm) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                prm.token = one_token()
                let resp = await api.savedoctor(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    await context.dispatch("search")
                    await context.dispatch("getdetail")
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_successMsg", 'Berhasil Simpan Data')
                    context.commit("update_loading", false)
                    context.commit("update_dialogDoctor", false)

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
        async generateFitnessCategory(context) {
            context.commit("update_lookup_status", 1)
            context.commit("update_loading", true)
            try {
                let prm = {
                    token: one_token(),
                    orderid: context.state.selectedPatient.orderID,
                    kesimpulan: context.state.patientDetail.kesimpulan,
                    rekomendasi: context.state.patientDetail.rekomendasi,
                    saran: context.state.patientDetail.saran,
                }
                let resp = await api.generateFitnessCategory(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_loading", false)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_snackbarError", true)

                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_errorMsg", '')
                    context.commit("update_snackbarSuccess", true)
                    context.commit("update_successMsg", 'Berhasil Simpan Data')
                    context.commit("update_loading", false)
                    await context.dispatch("search")
                    await context.dispatch("getdetail")
                    context.commit("update_dialogFitness", true);
                    let a = {};
                    for (let i = 0; i < resp.data.data.length; i++) {
                        resp.data.data[i].Nat_TestName.replace('|', ', ')
                    }

                    context.commit("update_dataFitness", resp.data);

                }
            } catch (e) {
                console.log(e)
                context.commit("update_lookup_status", 3)
                context.commit("update_loading", false)
                context.commit("update_errorMsg", e)
                context.commit("update_snackbarError", true)

            }
        },
    }
}
