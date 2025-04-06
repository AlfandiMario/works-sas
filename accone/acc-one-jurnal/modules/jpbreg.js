// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/jpbreg.js"

export default {
    namespaced: true,
    state: {
        last_id: -1,
        loading: false,
        dialogForm: false,
        dialogType: 'N',
        startDate: moment(new Date()).format('YYYY-MM-DD'),
        endDate: moment(new Date()).format('YYYY-MM-DD'),
        selectedDate: moment(new Date()).format('YYYY-MM-DD'),
        user: one_user(),
        jurnalTypeList: [],
        selectedJurnalType: {},
        balance: {
            debet: 0,
            kredit: 0,
            balance: 0,
        },
        balanceRegional: {
            debet: 0,
            kredit: 0,
            balance: 0,
        },
        defaultJurnalType: {},
        loading: false,
        periodeList: [],
        searchPeriode: '',
        selectedPeriode: {},
        loadingAutocomplete: false,
        title: '',
        coaList: [],
        searchCoa: '',
        selectedCoa: {},
        sallary: 0,
        description: '',
        branchPercentList: [],
        selectedBranchPercent: {},
        defaultBranch: [],
        detailJurnalGaji: [],
        detailJurnalGajiRegional: [],
        dialogAddDetail: false,
        coaListDetail: [],
        selectedCoaDetail: {},
        searchCoaDetail: '',
        coaListKas: [],
        selectedCoaKas: {},
        searchCoaKas: '',
        selectedBranchDetail: {},
        kreditDetail: 0,
        debetDetail: 0,
        snackbar: {
            state: false,
            color: 'success',
            msg: ''
        },
        idEdit: 0,
        isEdit: 'N',
        jurnalNumber: "",
    },
    mutations: {
        update_jurnalNumber(state, val) {
            state.jurnalNumber = val
        },
        update_debetDetail(state, val) {
            state.debetDetail = val
        },
        update_dialogType(state, val) {
            state.dialogType = val
        },
        update_idEdit(state, val) {
            state.idEdit = val
        },
        update_balance(state, val) {
            state.balance = val
        },
        update_balanceRegional(state, val) {
            state.balanceRegional = val
        },
        update_snackbar(state, val) {
            state.snackbar = val
        },
        update_kreditDetail(state, val) {
            state.kreditDetail = val
        },
        update_selectedBranchDetail(state, val) {
            state.selectedBranchDetail = val
        },
        update_coaListKas(state, val) {
            state.coaListKas = val
        },
        update_searchCoaKas(state, val) {
            state.searchCoaKas = val
        },
        update_selectedCoaKas(state, val) {
            state.selectedCoaKas = val
        },
        update_coaListDetail(state, val) {
            state.coaListDetail = val
        },
        update_searchCoaDetail(state, val) {
            state.searchCoaDetail = val
        },
        update_selectedCoaDetail(state, val) {
            state.selectedCoaDetail = val
        },
        update_detailJurnalGaji(state, val) {
            state.detailJurnalGaji = val
        },
        update_detailJurnalGajiRegional(state, val) {
            state.detailJurnalGajiRegional = val
        },
        update_dialogAddDetail(state, val) {
            state.dialogAddDetail = val
        },
        update_title(state, val) {
            state.title = val
        },
        update_defaultBranch(state, val) {
            state.defaultBranch = val
        },
        update_branchPercentList(state, val) {
            state.branchPercentList = val
        },
        update_selectedBranchPercent(state, val) {
            state.selectedBranchPercent = val
        },
        update_selectedDate(state, val) {
            state.selectedDate = val
        },
        update_loadingAutocomplete(state, val) {
            state.loadingAutocomplete = val
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
        update_selectedPeriode(state, val) {
            state.selectedPeriode = val
        },
        update_searchPeriode(state, val) {
            state.searchPeriode = val
        },
        update_periodeList(state, val) {
            state.periodeList = val
        },
        update_loading(state, val) {
            state.loading = val
        },
        update_defaultJurnalType(state, val) {
            state.defaultJurnalType = val
        },
        update_selectedJurnalType(state, val) {
            state.selectedJurnalType = val
        },
        update_jurnalTypeList(state, val) {
            state.jurnalTypeList = val
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
        update_description(state, val) {
            state.description = val
        },
        update_isEdit(state, val) {
            state.isEdit = val
        },
    },
    actions: {
        countSummary(context) {
            let kredit = 0;
            let debet = 0;
            context.state.detailJurnalGaji.forEach((e) => {
                if (e.type === "D") {
                    debet = parseFloat(e.debet) + debet;
                }
                if (e.type === "K") {
                    kredit = parseFloat(e.kredit) + kredit;
                }
            });
            let total = debet - kredit;

            let summary = {
                debet: debet,
                kredit: kredit,
                balance: total,
            };
            context.commit("update_balance", summary)
            // this.balance = summary;
        },
        countSummaryRegional(context) {
            let kredit = 0;
            let debet = 0;
            context.state.detailJurnalGajiRegional.forEach((e) => {
                if (e.type === "D") {
                    debet = parseFloat(e.debet) + debet;
                }
                if (e.type === "K") {
                    kredit = parseFloat(e.kredit) + kredit;
                }
            });
            let total = debet - kredit;

            let summary = {
                debet: debet,
                kredit: kredit,
                balance: total,
            };
            context.commit("update_balanceRegional", summary)

        },
        async getDetail(context, prm) {
            context.commit("update_loading", true)
            try {
                prm.token = one_token()
                let resp = await api.getDetail(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp.data);
                    // setTimeout(() => {
                    //     // Tambahkan logika yang ingin dijalankan setelah 3 detik di sini
                    //     console.log("Timer selesai, menjalankan sesuatu setelah 3 detik");
                    //     context.commit("update_loading", false)
                    // }, 3000);
                    context.commit("update_selectedPeriode", resp.data.periode)
                    context.commit("update_searchPeriode", resp.data.periode.display)
                    context.commit("update_title", resp.data.title)
                    // context.commit("update_selectedCoa", resp.data.coa)
                    // context.commit("update_searchCoa", resp.data.coa.display)
                    // context.commit("update_selectedCoaKas", resp.data.coaKas)
                    // context.commit("update_searchCoaKas", resp.data.coaKas.display ?? '')
                    // context.commit("update_sallary", resp.data.sallary)
                    context.commit("update_isEdit", resp.data.isEdit)
                    context.commit("update_jurnalNumber", resp.data.jurnal.jurnalNo)
                    context.commit("update_selectedBranchDetail", resp.data.destination)
                    context.commit("update_description", resp.data.description)
                    context.commit("update_detailJurnalGaji", resp.data.jurnalDetail)
                    // context.commit("update_detailJurnalGajiRegional", resp.data.jurnalDetailRegional)
                    // context.commit("update_dialogType", resp.data.type)
                    // context.commit("update_selectedBranchPercent", resp.data.branchPrecent)
                    context.dispatch("countSummary")
                    context.dispatch("countSummaryRegional")
                    context.commit("update_loading", false)

                    // let snackbar = {
                    //     state: true,
                    //     color: 'success',
                    //     msg: 'Berhasil tambah jurnal gaji regional'
                    // }
                    // context.commit("update_snackbar", snackbar)
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: e.message
                }
                context.commit("update_snackbar", snackbar)
            }
        },
        async saveJurnal(context, prm) {
            context.commit("update_loading", true)
            try {

                prm.token = one_token()
                let resp = await api.saveJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil tambah jurnal pengiriman barang ke cabang ' + prm.branchName
                    }
                    context.commit("update_snackbar", snackbar)

                    context.commit("update_selectedPeriode", {})
                    context.commit("update_selectedCoa", {})
                    context.commit("update_selectedCoaDetail", {})
                    context.commit("update_coaList", [])
                    context.commit("update_coaListKas", [])
                    context.commit("update_selectedCoaKas", {})
                    context.commit("update_selectedBranchPercent", {})
                    context.commit("update_selectedBranchDetail", {})

                    context.commit("update_detailJurnalGaji", [])
                    context.commit("update_detailJurnalGajiRegional", [])

                    let blc = {
                        debet: 0,
                        kredit: 0,
                        balance: 0,
                    };
                    context.commit("update_balance", blc)
                    context.commit("update_balanceRegional", blc)
                    context.commit("update_sallary", 0)
                    context.commit("update_searchCoa", '')
                    context.commit("update_searchCoaKas", '')
                    context.commit("update_title", '')
                    context.commit("update_description", '')
                    context.commit("update_dialogForm", false)
                    const jurnalumumState = context.rootState.jurnalumum;

                    context.dispatch("jurnalumum/search", {
                        regionalid: jurnalumumState.user.S_RegionalID,
                        branchid: jurnalumumState.user.M_BranchID,
                        current_page: jurnalumumState.current_page,
                        search: jurnalumumState.x_search,
                        startdate: jurnalumumState.start_date,
                        enddate: jurnalumumState.end_date,
                        last_id: -1,
                    }, { root: true });


                    // this.sallary = 0;
                    // this.searchCoa = "";
                    // this.searchCoaKas = "";
                    // this.title = "";
                    // this.description = "";
                    // console.log("close dialogs");
                    // this.$emit("update:show", false);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: e.message
                }
                context.commit("update_snackbar", snackbar)
            }
        },
        async editJurnal(context, prm) {
            context.commit("update_loading", true)
            try {

                prm.token = one_token()
                let resp = await api.editJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil ubah jurnal pengiriman barang ke cabang ' + prm.branchName
                    }
                    context.commit("update_snackbar", snackbar)

                    context.commit("update_selectedPeriode", {})
                    context.commit("update_selectedCoa", {})
                    context.commit("update_selectedCoaDetail", {})
                    context.commit("update_coaList", [])
                    context.commit("update_coaListKas", [])
                    context.commit("update_selectedCoaKas", {})
                    context.commit("update_selectedBranchPercent", {})
                    context.commit("update_selectedBranchDetail", {})

                    context.commit("update_detailJurnalGaji", [])
                    context.commit("update_detailJurnalGajiRegional", [])

                    let blc = {
                        debet: 0,
                        kredit: 0,
                        balance: 0,
                    };
                    context.commit("update_balance", blc)
                    context.commit("update_balanceRegional", blc)
                    context.commit("update_sallary", 0)
                    context.commit("update_searchCoa", '')
                    context.commit("update_searchCoaKas", '')
                    context.commit("update_title", '')
                    context.commit("update_description", '')
                    const jurnalumumState = context.rootState.jurnalumum;

                    context.dispatch("jurnalumum/search", {
                        regionalid: jurnalumumState.user.S_RegionalID,
                        branchid: jurnalumumState.user.M_BranchID,
                        current_page: jurnalumumState.current_page,
                        search: jurnalumumState.x_search,
                        startdate: jurnalumumState.start_date,
                        enddate: jurnalumumState.end_date,
                        last_id: -1,
                    }, { root: true });
                    context.commit("update_dialogForm", false)


                    // this.sallary = 0;
                    // this.searchCoa = "";
                    // this.searchCoaKas = "";
                    // this.title = "";
                    // this.description = "";
                    // console.log("close dialogs");
                    // this.$emit("update:show", false);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: e.message
                }
                context.commit("update_snackbar", snackbar)
            }
        },
        async getJurnalType(context) {
            context.commit("update_loading", true)
            try {
                let prm = {};
                prm.token = one_token()
                let resp = await api.getJurnalType(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    context.commit("update_jurnalTypeList", resp.data.records)
                    context.commit("update_selectedJurnalType", resp.data.default)
                    context.commit("update_defaultJurnalType", resp.data.default)

                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async getDefaultBranch(context) {
            context.commit("update_loading", true)
            try {
                let prm = {};
                prm.token = one_token()
                prm.company = context.state.user.M_BranchCompanyID
                prm.regional = context.state.user.S_RegionalID
                let resp = await api.getDefaultBranch(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    context.commit("update_defaultBranch", resp.data)
                    // context.commit("update_selectedJurnalType", resp.data.default)
                    // context.commit("update_defaultJurnalType", resp.data.default)

                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
            }
        },
        async deleteJurnal(context, prm) {
            context.commit("update_loading", true)
            try {

                prm.token = one_token()
                let resp = await api.deleteJurnal(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                    let snackbar = {
                        state: true,
                        color: 'error',
                        msg: resp.message
                    }
                    context.commit("update_snackbar", snackbar)
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    let snackbar = {
                        state: true,
                        color: 'success',
                        msg: 'Berhasil delete jurnal gaji regional'
                    }
                    context.commit("update_snackbar", snackbar)

                    context.commit("update_snackbar", snackbar)

                    context.commit("update_selectedPeriode", {})
                    context.commit("update_selectedCoa", {})
                    context.commit("update_selectedCoaDetail", {})
                    context.commit("update_coaList", [])
                    context.commit("update_coaListKas", [])
                    context.commit("update_selectedCoaKas", {})
                    context.commit("update_selectedBranchPercent", {})
                    context.commit("update_selectedBranchDetail", {})

                    context.commit("update_detailJurnalGaji", [])
                    context.commit("update_detailJurnalGajiRegional", [])

                    let blc = {
                        debet: 0,
                        kredit: 0,
                        balance: 0,
                    };
                    context.commit("update_balance", blc)
                    context.commit("update_balanceRegional", blc)
                    context.commit("update_sallary", 0)
                    context.commit("update_searchCoa", '')
                    context.commit("update_searchCoaKas", '')
                    context.commit("update_title", '')
                    context.commit("update_description", '')
                    context.commit("update_dialogForm", false)

                    const jurnalumumState = context.rootState.jurnalumum;

                    context.dispatch("jurnalumum/search", {
                        regionalid: jurnalumumState.user.S_RegionalID,
                        branchid: jurnalumumState.user.M_BranchID,
                        current_page: jurnalumumState.current_page,
                        search: jurnalumumState.x_search,
                        startdate: jurnalumumState.start_date,
                        enddate: jurnalumumState.end_date,
                        last_id: -1,
                    }, { root: true });




                    // this.sallary = 0;
                    // this.searchCoa = "";
                    // this.searchCoaKas = "";
                    // this.title = "";
                    // this.description = "";
                    // console.log("close dialogs");
                    // this.$emit("update:show", false);
                }
            } catch (e) {
                context.commit("update_loading", false)
                console.log(e)
                let snackbar = {
                    state: true,
                    color: 'error',
                    msg: e.message
                }
                context.commit("update_snackbar", snackbar)
            }
        },
        async getBranchpercent(context) {
            context.commit("update_loading", true)
            try {
                let prm = {};
                prm.token = one_token()
                prm.regional = context.state.user.S_RegionalID
                let resp = await api.searchBranchPercent(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading", false)
                    console.log(resp);
                } else {
                    console.log(resp);
                    context.commit("update_loading", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    context.commit("update_branchPercentList", resp.data)
                    // context.commit("update_selectedJurnalType", resp.data.default)
                    // context.commit("update_defaultJurnalType", resp.data.default)

                }
            } catch (e) {
                context.commit("update_loading", false)
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
                    console.log(resp);
                } else {
                    console.log(resp);
                    context.commit("update_loadingAutocomplete", false)
                    // let cek = 0;
                    // if (parseInt(resp.data) > 0) {
                    //     cek = true
                    // }
                    context.commit("update_periodeList", resp.data)

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
                prm.company = context.state.user.M_BranchCompanyID
                prm.regional = context.state.user.S_RegionalID

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
        async getCoaKas(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchCoaKas;
                prm.company = context.state.user.M_BranchCompanyID
                prm.regional = context.state.user.S_RegionalID

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
                    context.commit("update_coaListKas", resp.data)

                }
            } catch (e) {
                context.commit("update_loadingAutocomplete", false)
                console.log(e)
            }
        },
        async getCoaDetail(context) {
            context.commit("update_loadingAutocomplete", true)
            try {
                let prm = {};
                prm.search = context.state.searchCoaDetail;
                prm.company = context.state.user.M_BranchCompanyID
                prm.regional = context.state.user.S_RegionalID
                prm.branch = context.state.selectedBranchDetail.branchID
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
                    context.commit("update_coaListDetail", resp.data)

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