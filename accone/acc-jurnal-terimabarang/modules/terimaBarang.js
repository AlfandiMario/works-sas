// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/terimaBarang.js"

export default {
    namespaced: true,
    state: {
        user: one_user(),
        dialogJurnalTerimaBarang: false,
        selectedJurnalType: {},
        jurnalTypeList: [],
        defaultJurnalType: {},
        selectedDate: moment(new Date()).format('YYYY-MM-DD'),
        searchPeriode: '',
        selectedPeriode: {},
        periodeList: [],
        loadingAutocomplete: false,
        searchSupplier: '',
        supplierList: [],
        selectedSupplier: {},
        jurnalTitle: '',
        grniValue: '',
        invoiceValue: '',
        jurnalDescription: '',
        searchCoa: '',
        selectedCoa: {},
        coaList: [],
        kreditValue: 0,
        debitValue: 0,
        errorsAddDetail: [],
        errorsAddJurnal: [],
        detailsJurnalTerimaBarang: [],
        detailSum: { debit: 0, credit: 0, balance: 0 },
        loadingSave: false,
        dialogForm: false,
        snackbar: {
            state: false,
            color: 'success',
            msg: ''
        },
        act: "",
        // Untuk Edit Dialog
        companyInfo: {},
        isPosted: false,
        selectedJurnal: {},

        last_id: -1,
        loading: false,
        startDate: moment(new Date()).format('YYYY-MM-DD'),
        endDate: moment(new Date()).format('YYYY-MM-DD'),
        loading: false,
        sallary: 0,
        description: '',
        branchPercentList: [],
        selectedBranchPercent: {},
    },
    mutations: {
        update_dialogJurnalTerimaBarang(state, val) {
            state.dialogJurnalTerimaBarang = val
        },
        update_selectedJurnalType(state, val) {
            state.selectedJurnalType = val
        },
        update_jurnalTypeList(state, val) {
            state.jurnalTypeList = val
        },
        update_defaultJurnalType(state, val) {
            state.defaultJurnalType = val
        },
        update_selectedDate(state, val) {
            state.selectedDate = val
        },
        update_searchPeriode(state, val) {
            state.searchPeriode = val
        },
        update_selectedPeriode(state, val) {
            state.selectedPeriode = val
        },
        update_periodeList(state, val) {
            state.periodeList = val
        },
        update_loadingAutocomplete(state, val) {
            state.loadingAutocomplete = val
        },
        update_searchSupplier(state, val) {
            state.searchSupplier = val
        },
        update_selectedSupplier(state, val) {
            state.selectedSupplier = val
        },
        update_supplierList(state, val) {
            state.supplierList = val
        },
        update_jurnalTitle(state, val) {
            state.jurnalTitle = val
        },
        update_grniValue(state, val) {
            state.grniValue = val
        },
        update_invoiceValue(state, val) {
            state.invoiceValue = val
        },
        update_debitValue(state, val) {
            state.debitValue = val
        },
        update_jurnalDescription(state, val) {
            state.jurnalDescription = val
        },
        update_kreditValue(state, val) {
            state.kreditValue = val
        },
        update_coaList(state, val) {
            state.coaList = val
        },
        update_searchCoa(state, val) {
            state.searchCoa = val
        },
        update_selectedCoa(state, val) {
            state.selectedCoa = val
        },
        update_errorsAddDetail(state, val) {
            state.errorsAddDetail = val
        },
        update_errorsAddJurnal(state, val) {
            state.errorsAddJurnal = val
        },
        update_detailsJurnalTerimaBarang(state, val) {
            state.detailsJurnalTerimaBarang = val
        },
        update_detailSum(state, val) {
            state.detailSum = val
        },
        update_loadingSave(state, val) {
            state.loadingSave = val
        },
        update_snackbar(state, val) {
            state.snackbar = val
        },
        update_actForm(state, val) {
            state.act = val
        },
        update_companyInfo(state, val) {
            state.companyInfo = val
        },
        update_isPosted(state, val) {
            state.isPosted = val
        },
        update_selectedJurnal(state, val) {
            state.selectedJurnal = val
        },


        /* NOT USED YET */
        update_defaultBranch(state, val) {
            state.defaultBranch = val
        },
        update_branchPercentList(state, val) {
            state.branchPercentList = val
        },
        update_selectedBranchPercent(state, val) {
            state.selectedBranchPercent = val
        },
        update_loading(state, val) {
            state.loading = val
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
        update_sallary(state, val) {
            state.sallary = val
        },

    },
    actions: {
        async getJurnalType(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.token = one_token()
                let resp = await api.getJurnalType(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingAutocomplete", false)
                } else {
                    context.commit("update_loadingAutocomplete", false)
                    context.commit("update_jurnalTypeList", resp.data.records)
                    context.commit("update_selectedJurnalType", resp.data.default)
                    context.commit("update_defaultJurnalType", resp.data.default)
                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },

        async getPeriode(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchPeriode;
                prm.token = one_token()
                let resp = await api.searchPeriode(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingAutocomplete", false)
                } else {
                    context.commit("update_loadingAutocomplete", false)
                    context.commit("update_periodeList", resp.data)
                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },

        async getSupplier(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchSupplier;
                prm.token = one_token()
                let resp = await api.searchSupplier(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingAutocomplete", false)
                } else {
                    context.commit("update_loadingAutocomplete", false)
                    context.commit("update_supplierList", resp.data)
                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },

        async getCoa(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchCoa;
                prm.token = one_token()
                let resp = await api.searchCoa(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingAutocomplete", false)
                } else {
                    context.commit("update_loadingAutocomplete", false)
                    context.commit("update_coaList", resp.data.records)
                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },

        async saveJurnal(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                let resp = await api.saveJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    context.commit("jurnalumum/update_save_error_message", resp.message, { root: true })
                    context.commit("jurnalumum/update_alert_error", true, { root: true })
                    console.log(resp);
                } else {
                    context.commit("jurnalumum/update_alert_success", true)
                    var msg = " Journal " + prm.jurnalTitle + " berhasil disimpan"
                    context.commit("jurnalumum/update_msg_success", msg, { root: true })

                    context.dispatch('jurnalumum/search', {
                        current_page: context.rootState.jurnalumum.current_page,
                        search: context.rootState.jurnalumum.x_search,
                        startdate: context.rootState.jurnalumum.start_date,
                        enddate: context.rootState.jurnalumum.end_date,
                        regionalid: context.rootState.jurnalumum.user.S_RegionalID,
                        branchid: context.rootState.jurnalumum.user.M_BranchID,
                        last_id: -1
                    }, { root: true })

                    context.commit("update_dialogForm", false)
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                console.log(e)
            }
        },

        async loadEditDialog(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                let resp = await api.loadEditDialog(prm)
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

                    context.commit("update_selectedJurnal", resp.data.jurnalHead)
                    context.commit("update_selectedJurnalType", {
                        JurnalTypeID: resp.data.jurnalHead.JurnalTypeID,
                        JurnalTypeName: resp.data.jurnalHead.JurnalTypeName
                    })
                    context.commit("update_selectedDate", resp.data.jurnalHead.jurnalDate)
                    context.commit("update_jurnalTitle", resp.data.jurnalHead.jurnalTitle)
                    context.commit("update_jurnalDescription", resp.data.jurnalHead.jurnalDescription)
                    context.commit("update_grniValue", resp.data.jurnalHead.GRNIGR ? resp.data.jurnalHead.GRNIGR : '')
                    context.commit("update_invoiceValue", resp.data.jurnalHead.INVGR ? resp.data.jurnalHead.INVGR : '')
                    context.commit("update_isPosted", resp.data.jurnalHead.jurnalIsPosted)

                    context.commit("update_selectedSupplier", resp.data.supplier)
                    context.commit("update_selectedPeriode", resp.data.periode)
                    context.commit("update_detailsJurnalTerimaBarang", resp.data.details)

                    // Hitung sum
                    let totalDebit = 0, totalCredit = 0
                    resp.data.details.forEach((item) => {
                        totalDebit += parseFloat(item.jurnalTxDebit)
                        totalCredit += parseFloat(item.jurnalTxCredit)
                    })
                    let totalBalance = totalDebit - totalCredit
                    context.commit("update_detailSum", {
                        debit: totalDebit,
                        credit: totalCredit,
                        balance: totalBalance
                    })

                    context.commit("update_dialogJurnalTerimaBarang", true)
                    context.commit("update_actForm", 'edit')
                    console.log(resp.data);
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                console.log(e)
            }
        },

        async updateJurnal(context, prm) {
            context.commit("update_loadingSave", true)
            try {
                prm.token = one_token()
                let resp = await api.updateJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loadingSave", false)
                    context.commit("jurnalumum/update_save_error_message", resp.message, { root: true })
                    context.commit("jurnalumum/update_alert_error", true, { root: true })
                    console.log(resp)
                } else {
                    context.commit("update_loadingSave", false)
                    context.commit("jurnalumum/update_alert_success", true, { root: true })
                    var msg = " Journal " + prm.jurnalTitle + " berhasil diupdate"
                    context.commit("jurnalumum/update_msg_success", msg, { root: true })
                    context.dispatch('jurnalumum/search', {
                        current_page: context.rootState.jurnalumum.current_page,
                        search: context.rootState.jurnalumum.x_search,
                        startdate: context.rootState.jurnalumum.start_date,
                        enddate: context.rootState.jurnalumum.end_date,
                        regionalid: context.rootState.jurnalumum.user.S_RegionalID,
                        branchid: context.rootState.jurnalumum.user.M_BranchID,
                        last_id: -1
                    }, { root: true })
                    console.log(resp)
                }
            } catch (e) {
                context.commit("update_loadingSave", false)
                console.log(e)
            }
        },


    }
}