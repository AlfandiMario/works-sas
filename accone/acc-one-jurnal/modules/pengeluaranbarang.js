import * as api from "../api/pengeluaranbarang.js";

export default {
    namespaced: true,
    state: {
        search_status: 0,
        insert_status: 0,
        insert_error: '',
        current_page: 1,
        
        user: one_user(),
        keluar_barang_dialog: false,
        selected_jurnal_type: {},
        periode_start_date: moment(new Date()).format('YYYY-MM-DD'),
        periode_end_date: moment(new Date()).format('YYYY-MM-DD'),
        
        input_company: one_user().M_BranchCompanyName,
        input_regional: one_user().S_RegionalName,
        input_cabang: one_user().M_BranchName,
        input_date: moment(new Date()).format('YYYY-MM-DD'),
        input_title: "",
        input_description: "",
        list_periode: [],
        selected_periode: {},


        detail_dialog: false,
        table_detail: [],
        list_coa: [],
        selected_coa: {},
        input_detail_debet: 0,
        input_detail_kredit: 0,
        summary_detail: {
            total_debet: 0,
            total_kredit: 0,
            total_balance: 0
        },

        opp_type: "add",
        crud_jurnal_status: 0,
        crud_jurnal: {
            state: false,
            msg: "",
            color: "error"
        },

        current_jurnal: {}
    },
    mutations: {
        update_search_status(state, val) {
            state.search_status = val
        },
        update_insert_status(state, val) {
            state.insert_status = val
        },
        update_insert_error(state, val) {
            state.insert_error = val
        },
        update_current_page(state, val) {
            state.current_page = val
        },
        update_user(state, val) {
            state.user = val
        },
        update_pengeluaran_barang_dialog(state, val) {
            state.keluar_barang_dialog = val
        },
        update_selected_jurnal_type(state, val) {
            state.selected_jurnal_type = val
        },
        update_periode_start_date(state, val) {
            state.periode_start_date = val
        },
        update_periode_end_date(state, val) {
            state.periode_end_date = val
        },
        update_input_company(state, val) {
            state.input_company = val
        },
        update_input_regional(state, val) {
            state.input_regional = val
        },
        update_input_cabang(state, val) {
            state.input_cabang = val
        },
        update_input_date(state, val) {
            state.input_date = val
        },
        update_input_title(state, val) {
            state.input_title = val
        },
        update_input_description(state, val) {
            state.input_description = val
        },
        update_list_periode(state, val) {
            state.list_periode = val
        },
        update_selected_periode(state, val) {
            state.selected_periode = val
        },
        update_list_coa(state, val) {
            state.list_coa = val
        },
        update_selected_coa(state, val) {
            state.selected_coa = val
        },
        update_detail_dialog(state, val) {
            state.detail_dialog = val
        },
        update_table_detail(state, val) {
            state.table_detail = val
        },
        update_summary_detail(state, val) {
            state.summary_detail = val
        },
        update_input_detail_debet(state, val) {
            state.input_detail_debet = val
        },
        update_input_detail_kredit(state, val) {
            state.input_detail_kredit = val
        },
        update_crud_jurnal_status(state, val) {
            state.crud_jurnal_status = val
        },
        update_crud_jurnal(state, val) {
            state.crud_jurnal = val
        },
        update_opp_type(state, val) {
            state.opp_type = val
        },
        update_current_jurnal(state, val) {
            state.current_jurnal = val
        }
    },
    actions: {
        async getperiode(context, params) {
            context.commit("update_search_status", 1)
            try {
                params.token = one_token()
                let resp = await api.get_periode(params)
                if (resp.status != "OK") {
                    context.commit("update_search_status", 3)
                } else {
                    context.commit("update_list_periode", resp.data.records)
                    context.commit("update_search_status", 2)
                }
            } catch (error) {
                context.commit("update_search_status", 3)
            }
        },
        async getcoa(context, params) {
            context.commit("update_search_status", 1)
            try {
                params.token = one_token()
                let resp = await api.get_coa(params)
                if (resp.status != "OK") {
                    context.commit("update_search_status", 3)
                } else {
                    context.commit("update_list_coa", resp.data.records)
                    context.commit("update_search_status", 2)
                }
            } catch (error) {
                context.commit("update_search_status", 3)
            }
        },
        async add_detail_table(context, params) {
            context.commit("update_insert_status", 1)
            try {
                let curr_detail = context.state.table_detail
                curr_detail.push(params)

                let k = 0
                let d = 0
                curr_detail.forEach(item => {
                     d += parseFloat(Number(item.debet).toFixed(2))
                     k += parseFloat(Number(item.kredit).toFixed(2))
                });

                let summary = {
                    total_kredit: k,
                    total_debet: d,
                    total_balance: (d - k)
                }
                

                context.commit("update_table_detail", curr_detail)
                context.commit("update_summary_detail", summary)
                context.commit("update_insert_status", 2)
            } catch (error) {
                context.commit("update_insert_error", "error insert detail jurnal")
                context.commit("update_insert_status", 3)
            }
        },
        async del_detail_table(context, params) {
            context.commit("update_insert_status", 1)
            try {
                let curr_detail = context.state.table_detail
                if (params > -1) {
                    curr_detail.splice(params, 1)
                }

                let k = 0
                let d = 0
                if (Object.keys(curr_detail).length > 0) {
                    curr_detail.forEach(item => {
                        d += parseFloat(Number(item.debet).toFixed(2))
                        k += parseFloat(Number(item.kredit).toFixed(2))
                    })
                }

                let summary = {
                    total_kredit: k,
                    total_debet: d,
                    total_balance: (d - k)
                }

                context.commit("update_table_detail", curr_detail)
                context.commit("update_summary_detail", summary)
                context.commit("update_insert_status", 2)
            } catch (error) {
                context.commit("update_insert_error", "error delete detail jurnal")
                context.commit("update_insert_status", 3)
            }
        },
        async simpan_jurnal(context, params) {
            context.commit("update_crud_jurnal_status", 1)
            try {
                params.token = one_token()
                let resp = await api.simpanjurnal(params)
                if (resp.status != "OK") {
                    let a = {
                        state: true,
                        msg: resp.message,
                        color: "error"
                    }
                    context.commit("update_crud_jurnal", a)
                    context.commit("update_crud_jurnal_status", 3)
                } else {
                    let a = {
                        state: true,
                        msg: "Sukses",
                        color: "success"
                    }
                    context.commit("update_crud_jurnal", a)
                    context.commit("update_crud_jurnal_status", 2)
                    context.commit("update_pengeluaran_barang_dialog", false)

                    context.dispatch('jurnalumum/search', {
                        current_page: context.rootState.jurnalumum.current_page,
                        search: context.rootState.jurnalumum.x_search,
                        startdate: context.rootState.jurnalumum.start_date,
                        enddate: context.rootState.jurnalumum.end_date,
                        regionalid: context.rootState.jurnalumum.user.S_RegionalID,
                        branchid: context.rootState.jurnalumum.user.M_BranchID,
                        last_id: -1
                    }, { root: true })
                }
            } catch (error) {
                let a = {
                    state: true,
                    msg: error.message,
                    color: "error"
                }
                context.commit("update_crud_jurnal", a)
                context.commit("update_crud_jurnal_status", 3)
            }
        },
        async load_jurnal(context, params) {
            try {
                params.token = one_token()
                let resp = await api.getdetailjurnal(params)
                if (resp.status != "OK") {
                    
                } else {
                    let sel_jurnal = {
                        JurnalTypeID: resp.data.jurnal.JurnalTypeID,
                        JurnalTypeCode: resp.data.jurnal.JurnalTypeCode,
                        JurnalTypeName: resp.data.jurnal.JurnalTypeName,
                        JurnalTypeAccesRight: resp.data.jurnal.JurnalTypeAccesRight,
                        JurnalTypeIsActive: resp.data.jurnal.JurnalTypeIsActive
                    }
                    context.commit("update_selected_jurnal_type", sel_jurnal)

                    let sel_periode = resp.data.periode
                    context.commit("update_selected_periode", sel_periode)

                    let in_title = resp.data.jurnal.jurnalTitle
                    context.commit("update_input_title", in_title)
                    let in_desc = resp.data.jurnal.jurnalDescription
                    context.commit("update_input_description", in_desc)
                    let in_date = resp.data.jurnal.jurnalDate
                    context.commit("update_input_date", in_date)

                    let j_detail = resp.data.jurnaldetail
                    context.commit("update_table_detail", j_detail)

                    let k = 0
                    let d = 0
                    j_detail.forEach(item => {
                        d += parseFloat(Number(item.debet).toFixed(2))
                        k += parseFloat(Number(item.kredit).toFixed(2))
                    })
                    
                    let summary = {
                        total_kredit: k,
                        total_debet: d,
                        total_balance: (d - k)
                    }
                    context.commit("update_summary_detail", summary)
                    context.commit("update_current_jurnal", resp.data.jurnal)
                }
            } catch (error) {
                let a = {
                    state: true,
                    msg: error.message,
                    color: "error"
                }
                context.commit("update_crud_jurnal", a)
                context.commit("update_crud_jurnal_status", 3)
            }
        },
        async edit_jurnal(context, params) {
            try {
                context.commit("update_opp_type", "edit")
                context.dispatch("load_jurnal", params)
                context.commit("update_pengeluaran_barang_dialog", true)
            } catch (error) {
                console.log(error)
            }
        },
        async simpan_edit_jurnal(context, params) {
            context.commit("update_crud_jurnal_status", 1)
            try {
                params.token = one_token()
                let resp = await api.editdetailjurnal(params)
                if (resp.status != "OK") {
                    let a = {
                        state: true,
                        msg: resp.message,
                        color: "error"
                    }
                    context.commit("update_crud_jurnal", a)
                    context.commit("update_crud_jurnal_status", 3)
                } else {
                    let a = {
                        state: true,
                        msg: "Sukses",
                        color: "success"
                    }
                    context.commit("update_crud_jurnal", a)
                    context.commit("update_crud_jurnal_status", 2)
                    context.commit("update_pengeluaran_barang_dialog", false)

                    context.dispatch('jurnalumum/search', {
                        current_page: context.rootState.jurnalumum.current_page,
                        search: context.rootState.jurnalumum.x_search,
                        startdate: context.rootState.jurnalumum.start_date,
                        enddate: context.rootState.jurnalumum.end_date,
                        regionalid: context.rootState.jurnalumum.user.S_RegionalID,
                        branchid: context.rootState.jurnalumum.user.M_BranchID,
                        last_id: -1
                    }, { root: true })
                }
            } catch (error) {
                let a = {
                    state: true,
                    msg: error.message,
                    color: "error"
                }
                context.commit("update_crud_jurnal", a)
                context.commit("update_crud_jurnal_status", 3)
            }
        },
        async add_jurnal(context) {
            try {
                context.commit("update_opp_type", "add")

                context.commit("update_input_company", context.state.user.M_BranchCompanyName)
                context.commit("update_input_regional", context.state.user.S_RegionalName)
                context.commit("update_input_cabang", context.state.user.M_BranchName)
                context.commit("update_selected_jurnal_type", context.rootState.jurnalumum.x_drop_tipe_jurnal)

                context.commit("update_selected_periode", {})
                context.commit("update_input_date", moment(new Date()).format('YYYY-MM-DD'))
                context.commit("update_input_title", "")
                context.commit("update_input_description", "")
                
                context.commit("update_table_detail", [])
                context.commit("update_selected_coa", {})
                context.commit("update_input_detail_debet", 0)
                context.commit("update_input_detail_kredit", 0)
                let summary = {
                    total_kredit: 0,
                    total_debet: 0,
                    total_balance: 0
                }
                context.commit("update_summary_detail", summary)
                context.commit("update_pengeluaran_barang_dialog", true)
            } catch (error) {
                console.log(error)
            }
        }
    }
};