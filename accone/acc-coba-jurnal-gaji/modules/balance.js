// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/balance.js"

export default {
    namespaced: true,
    state: {
        last_id: -1,
        loading: false,
        loadingSave: false,
        cek: false,
        dataUpload: [],
        status: 'N',
        snackbar: {
            state: false,
            color: 'success',
            msg: ''
        },
        periodeList: [
            {
                "id": '1', "name": "periode Januari", "periode": '01-01-2024 - 31-01-2024'
            },
            {
                "id": '2', "name": "periode Februari", "periode": '01-02-2024 - 31-02-2024'
            }
        ],
        selectedPeriode: {},
        data: [
            {
                "number": '101101001',
                "keterangan": 'Bank BCA 742-599-268899',
                "value": '1500000',
                "type": 'DB',
                "input": 1
            },
            {
                "number": '101101002',
                "keterangan": 'Bank',
                "value": '1500000',
                "type": 'CR',
                "input": 1
            },
        ],
        summary: { debit: "1500000", credit: "1500000", balance: "1500000" },
        dialogEdit: false,
        dialogImport: false,
        dialogAdd: false,
        loadingAutocomplete: false,
        coaList: [],
        selectedCoa: {},
        searchCoa: ''
    },
    mutations: {
        update_periodeList(state, val) {
            state.periodeList = val
        },
        update_loadingSave(state, val) {
            state.loadingSave = val
        },
        update_selectedPeriode(state, val) {
            state.selectedPeriode = val
        },
        update_data(state, val) {
            state.data = val
        },
        update_dialogEdit(state, val) {
            state.dialogEdit = val
        },
        update_dialogImport(state, val) {
            state.dialogImport = val
        },
        update_summary(state, val) {
            state.summary = val
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
        update_dialogAdd(state, val) {
            state.dialogAdd = val
        },
        update_status(state, val) {
            state.status = val
        },
        update_snackbar(state, val) {
            state.snackbar = val
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
                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async addData(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.periode = context.state.selectedPeriode;
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
                    context.dispatch("search");
                    context.commit("update_dialogAdd", false)
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
        async save(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.periode = context.state.selectedPeriode;
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
                    context.commit("update_loadingSave", false)
                    context.dispatch("search");
                    context.commit("update_dialogImport", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil simpan data'
                    }
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
        async postData(context) {
            context.commit("update_loadingSave", true)
            try {
                let prm = {}
                prm.token = one_token()
                let resp = await api.postData(prm)
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
                    context.commit("update_loadingSave", false)
                    context.dispatch("search");
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil post data'
                    }
                    context.commit("update_snackbar", snackbar)


                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error post data"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async search(context) {
            context.commit("update_loading", true)
            try {
                let prm = {}
                prm.token = one_token()
                let resp = await api.search(prm)
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
                    context.commit("update_data", resp.data.data)
                    context.commit("update_summary", resp.data.total)
                    context.commit("update_selectedPeriode", resp.data.periode)
                    context.commit("update_status", resp.data.status)
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
        async cek(context) {
            context.commit("update_loading", true)
            try {
                let prm = {}
                prm.token = one_token()
                let resp = await api.cek(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp)
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    let cek = 0;
                    if (parseInt(resp.data) > 0) {
                        cek = true
                    }
                    context.commit("update_cek", cek)

                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async updateData(context, prm) {
            context.commit("update_loadingSave", true)
            try {

                prm.token = one_token()
                let resp = await api.updateData(prm)
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
                    context.commit("update_loadingSave", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    context.dispatch("search")
                    context.commit("update_dialogEdit", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil update data'
                    }
                    context.commit("update_snackbar", snackbar)

                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error update data"
                }
                context.commit("update_snackbar", snackbar)
                console.log(e)
            }
        },
        async deleteData(context, prm) {
            context.commit("update_loadingSave", true)
            try {

                prm.token = one_token()
                let resp = await api.deleteData(prm)
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
                    context.commit("update_loadingSave", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil hapus data'
                    }
                    context.commit("update_snackbar", snackbar)
                    context.dispatch("search")
                    // context.commit("update_dialogEdit", false)

                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: "Error delete data"
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
    }
}