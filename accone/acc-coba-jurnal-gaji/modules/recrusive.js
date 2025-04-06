// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/recrusive.js"

export default {
    namespaced: true,
    state: {
        last_id: -1,
        loading: false,
        dialogForm: false,
        startDate: moment(new Date()).format('YYYY-MM-DD'),
        endDate: moment(new Date()).format('YYYY-MM-DD'),
        kreditList: [],
        selectedKredit: {},
        searchKredit: '',
        debetList: [],
        selectedDebet: {},
        searchDebet: '',
        loadingAutocomplete: false,
        tenor: 0,
        loadingSave: false,
        prePaid: 0,
        bulanan: 0,
        snackbar: {
            state: false,
            color: 'success',
            msg: ''
        },
        templateList: [],
        page: 1,
        total: 0,
        searchTemplate: '',
        selectedTemplate: {},
        dialogAlert: '',
        defaultBranch: [],


    },
    mutations: {
        update_defaultBranch(state, val) {
            state.defaultBranch = val
        },
        update_dialogAlert(state, val) {
            state.dialogAlert = val
        },
        update_selectedTemplate(state, val) {
            state.selectedTemplate = val
        },
        update_searchTemplate(state, val) {
            state.searchTemplate = val
        },
        update_total(state, val) {
            state.total = val
        },
        update_page(state, val) {
            state.page = val
        },
        update_templateList(state, val) {
            state.templateList = val
        },
        update_snackbar(state, val) {
            state.snackbar = val
        },
        update_loadingSave(state, val) {
            state.loadingSave = val
        },
        update_tenor(state, val) {
            state.tenor = val
        },
        update_loading(state, val) {
            state.loading = val
        },
        update_prePaid(state, val) {
            state.prePaid = val
        },
        update_bulanan(state, val) {
            state.bulanan = val
        },
        update_loadingAutocomplete(state, val) {
            state.loadingAutocomplete = val
        },
        update_periodeList(state, val) {
            state.periodeList = val
        },
        update_dialogForm(state, val) {
            state.dialogForm = val
        },
        update_startDate(state, val) {
            state.startDate = val
        },
        update_endDate(state, val) {
            state.endDate = val
        },
        update_kreditList(state, val) {
            state.kreditList = val
        },
        update_selectedKredit(state, val) {
            state.selectedKredit = val
        },
        update_searchKredit(state, val) {
            state.searchKredit = val
        },
        update_debetList(state, val) {
            state.debetList = val
        },
        update_selectedDebet(state, val) {
            state.selectedDebet = val
        },
        update_searchDebet(state, val) {
            state.searchDebet = val
        },

    },
    actions: {
        async searchDebet(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchDebet;
                prm.token = one_token()
                let resp = await api.searchDebet(prm)
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
                    context.commit("update_debetList", resp.data)

                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },
        async searchKredit(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchKredit;
                prm.token = one_token()
                let resp = await api.searchkredit(prm)
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
                    context.commit("update_kreditList", resp.data)

                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },
        async search(context) {
            context.commit("update_loading", true)
            try {
                let prm = {}
                prm.search = context.state.searchTemplate
                prm.page = context.state.page
                prm.token = one_token()
                let resp = await api.search(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                } else {
                    context.commit("update_loading", false)
                    context.commit("update_templateList", resp.data.records)
                    context.commit("update_total", resp.data.total)
                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async insertTemplate(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                let resp = await api.insertTemplate(prm)
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
                    context.dispatch("search");
                    context.commit("update_dialogForm", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah data'
                    }
                    context.commit("update_snackbar", snackbar)

                    context.commit("update_loadingSave", false)

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
        async updateTemplate(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                let resp = await api.updateTemplate(prm)
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
                    context.dispatch("search");
                    context.commit("update_dialogForm", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil edit data'
                    }
                    context.commit("update_snackbar", snackbar)

                    context.commit("update_loadingSave", false)

                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: 'Error edit data'
                }
                context.commit("update_snackbar", snackbar)
                console.log('error edit data')
            }
        },
        async deleteTemplate(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                let resp = await api.deleteTemplate(prm)
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
                    context.dispatch("search");
                    context.commit("update_dialogAlert", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil delete data'
                    }
                    context.commit("update_snackbar", snackbar)

                    context.commit("update_loadingSave", false)

                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: 'Error edit data'
                }
                context.commit("update_snackbar", snackbar)
                console.log('error delete data')
            }
        },

    }
}