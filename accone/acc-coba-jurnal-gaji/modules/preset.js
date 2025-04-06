// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/preset.js"

export default {
    namespaced: true,
    state: {
        last_id: -1,
        loading: false,
        cek: false,
        dataUpload: [],
        status: 'N',
        snackbar: {
            state: false,
            color: 'success',
            msg: ''
        },
        periodeList: [
        ],
        jurnalType: [
            "UMUM"
        ],
        selectedJurnalType: 'UMUM',
        selectedPeriode: {},
        selectedPeriodeForm: {},
        dataHeader: [

        ],
        dataDetail: [
        ],
        summaryDetail: { debit: "0", credit: "0", balance: "0" },
        dialogEdit: false,
        loadingSave: false,
        dialogAlert: false,
        dialogImport: false,
        dialogAddHeader: false,
        dialogAddDetail: false,
        dialogPost: false,
        loadingAutocomplete: false,
        coaList: [],
        selectedCoa: {},
        searchCoa: '',
        branchList: [],
        selectedBranch: {},
        searchHeader: "",
        searchDetail: "",
        pageHeader: 1,
        totalPageHeader: 0,
        pageDetail: 1,
        totalPageDetail: 0,
        selectedHeader: {},
        alertMsg: '',
        actAlert: ''
    },
    mutations: {
        update_alertMsg(state, val) {
            state.alertMsg = val
        },
        update_actAlert(state, val) {
            state.actAlert = val
        },
        update_loadingSave(state, val) {
            state.loadingSave = val
        },
        update_dialogAlert(state, val) {
            state.dialogAlert = val
        },
        update_dialogPost(state, val) {
            state.dialogPost = val
        },
        update_selectedHeader(state, val) {
            state.selectedHeader = val
        },
        update_jurnalType(state, val) {
            state.jurnalType = val
        },
        update_selectedJurnalType(state, val) {
            state.selectedJurnalType = val
        },
        update_periodeList(state, val) {
            state.periodeList = val
        },
        update_selectedPeriode(state, val) {
            state.selectedPeriode = val
        },
        update_dataHeader(state, val) {
            state.dataHeader = val
        },
        update_dataDetail(state, val) {
            state.dataDetail = val
        },
        update_dialogEdit(state, val) {
            state.dialogEdit = val
        },
        update_dialogImport(state, val) {
            state.dialogImport = val
        },
        update_summaryDetail(state, val) {
            state.summaryDetail = val
        },
        update_loading(state, val) {
            state.loading = val
        },
        update_cek(state, val) {
            state.cek = val
        },
        update_dataUpload(state, val) {
            state.dataUpload = val
        },
        update_loadingAutocomplete(state, val) {
            state.loadingAutocomplete = val
        },
        update_coaList(state, val) {
            state.coaList = val
        },
        update_selectedCoa(state, val) {
            state.selectedCoa = val
        },
        update_searchCoa(state, val) {
            state.searchCoa = val
        },
        update_dialogAddHeader(state, val) {
            state.dialogAddHeader = val
        },
        update_status(state, val) {
            state.status = val
        },
        update_snackbar(state, val) {
            state.snackbar = val
        },
        update_selectedPeriodeForm(state, val) {
            state.selectedPeriodeForm = val
        },
        update_branchList(state, val) {
            state.branchList = val
        },
        update_selectedBranch(state, val) {
            state.selectedBranch = val
        },
        update_searchHeader(state, val) {
            state.searchHeader = val
        },
        update_searchDetail(state, val) {
            state.searchDetail = val
        },
        update_pageHeader(state, val) {
            state.pageHeader = val
        },
        update_totalPageHeader(state, val) {
            state.totalPageHeader = val
        },
        update_pageDetail(state, val) {
            state.pageDetail = val
        },
        update_totalPageDetail(state, val) {
            state.totalPageDetail = val
        },
        update_dialogAddDetail(state, val) {
            state.dialogAddDetail = val
        },
    },
    actions: {
        async getPeriode(context) {
            context.commit("update_loading", true)
            try {
                let prm = {}
                prm.token = one_token()
                let resp = await api.getPeriode(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                } else {
                    context.commit("update_loading", false)
                    context.commit("update_periodeList", resp.data)
                    if (resp.data.length > 0) {
                        context.commit("update_selectedPeriode", resp.data[0])
                        context.dispatch("searchHeader")

                    }
                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async getBranch(context) {
            context.commit("update_loading", true)
            try {
                let prm = {}
                prm.token = one_token()
                let resp = await api.getBranch(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                } else {
                    context.commit("update_loading", false)
                    context.commit("update_branchList", resp.data)
                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async addJurnal(context, prm) {
            context.commit("update_loadingSave", true)
            try {

                prm.token = one_token()
                let resp = await api.addJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    console.log(resp)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    await context.dispatch("searchHeader")
                    context.commit("update_dialogAddHeader", false)
                    context.commit("update_loadingSave", false)

                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah data ' + resp.data
                    }
                    context.commit("update_snackbar", snackbar)

                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error add data"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async editJurnal(context, prm) {
            context.commit("update_loadingSave", true)
            try {

                prm.token = one_token()
                let resp = await api.editJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    console.log(resp)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    await context.dispatch("searchHeader")
                    context.commit("update_dialogAddHeader", false)
                    context.commit("update_loadingSave", false)

                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah data ' + resp.data
                    }
                    context.commit("update_snackbar", snackbar)

                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error add data"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async deleteJurnal(context, prm) {
            context.commit("update_loadingSave", true)
            try {

                prm.token = one_token()
                let resp = await api.deleteJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    console.log(resp)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    context.commit("update_selectedHeader", {})

                    console.log(resp);
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    await context.dispatch("searchHeader")
                    context.commit("update_dialogAlert", false)
                    context.commit("update_loadingSave", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil hapus data ' + resp.data
                    }
                    context.commit("update_snackbar", snackbar)

                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error add data"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async searchHeader(context) {
            context.commit("update_loading", true)
            try {
                let prm = {
                    page: context.state.pageHeader,
                    periode: context.state.selectedPeriode.id,
                    search: context.state.searchHeader
                }
                prm.token = one_token()
                let resp = await api.searchHeader(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);

                    context.commit("update_dataHeader", resp.data.records)
                    context.commit("update_totalPageHeader", resp.data.total)
                    let dataCek = resp.data.records;
                    if (dataCek.length > 0) {
                        if (Object.keys(context.state.selectedHeader).length !== 0) {
                            let selected = dataCek.find((e) => e.id === context.state.selectedHeader.id)
                            context.commit("update_selectedHeader", selected)

                        }
                    }
                    context.commit("update_loading", false)
                    console.log(resp.data);

                }
            } catch (e) {
                context.commit("update_loading", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error add data"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async searchCoa(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchCoa;
                prm.jurnal = context.state.selectedHeader;
                prm.token = one_token()
                let resp = await api.searchCoa(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingAutocomplete", false)
                    console.log(resp);
                } else {
                    console.log(resp);
                    context.commit("update_loadingAutocomplete", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }

                    context.commit("update_coaList", resp.data)

                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },
        async save(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.jurnal = context.state.selectedHeader;
                prm.token = one_token()
                let resp = await api.save(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                    console.log(resp)
                } else {
                    await context.dispatch("searchDetail");
                    context.commit("update_dialogImport", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil simpan data'
                    }
                    context.commit("update_loadingSave", false)
                    context.commit("update_snackbar", snackbar)

                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error save"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async addData(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.jurnal = context.state.selectedHeader;
                prm.token = one_token()
                let resp = await api.addData(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                    console.log(resp)
                } else {
                    context.commit("update_loadingSave", false)
                    // context.dispatch("search");
                    await context.dispatch("searchDetail");
                    context.commit("update_dialogAddDetail", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah data'
                    }
                    context.commit("update_snackbar", snackbar)


                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: 'Error add data'
                }
                context.commit("update_snackbar", snackbar)
                console.log('error add data')
            }
        },
        async updateData(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.coa = context.state.selectedCoa;
                prm.token = one_token()
                let resp = await api.updateData(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                    console.log(resp)
                } else {
                    context.commit("update_loadingSave", false)
                    // context.dispatch("search");
                    await context.dispatch("searchDetail");
                    context.commit("update_dialogAddDetail", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah data'
                    }
                    context.commit("update_snackbar", snackbar)


                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: 'Error add data'
                }
                context.commit("update_snackbar", snackbar)
                console.log('error add data')
            }
        },
        async deleteData(context, prm) {
            context.commit("update_loadingSave", true)
            try {

                prm.token = one_token()
                let resp = await api.deleteData(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                    console.log(resp)
                } else {
                    context.commit("update_loadingSave", false)
                    // context.dispatch("search");
                    context.commit("update_dialogAlert", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah data'
                    }
                    context.commit("update_snackbar", snackbar)
                    await context.dispatch("searchDetail");


                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: 'Error add data'
                }
                context.commit("update_snackbar", snackbar)
                console.log('error add data')
            }
        },
        async searchDetail(context) {
            context.commit("update_loading", true)
            try {
                let prm = {}
                prm.jurnal = context.state.selectedHeader
                prm.page = context.state.pageDetail
                prm.search = context.state.searchDetail
                prm.token = one_token()
                let resp = await api.searchDetail(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    context.commit("update_dataDetail", resp.data.data)
                    context.commit("update_summaryDetail", resp.data.summary)
                    context.commit("update_totalPageDetail", resp.data.total)

                }
            } catch (e) {
                context.commit("update_loading", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error Search"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async postData(context) {
            context.commit("update_loadingSave", true)
            try {
                let prm = {};
                prm.id = context.state.selectedHeader.id;
                prm.token = one_token()
                let resp = await api.postData(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                    console.log(resp)
                } else {

                    await context.dispatch("searchHeader");
                    await context.dispatch("searchDetail");
                    context.commit("update_dialogAddDetail", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil post data'
                    }
                    context.commit("update_snackbar", snackbar)


                    console.log(resp.data);
                    context.commit("update_dialogPost", false)
                    context.commit("update_loadingSave", false)
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: 'Error add data'
                }
                context.commit("update_snackbar", snackbar)
                console.log('error add data')
            }
        },
    }
}