// 1 => LOADING
// 2 => DONE
// 3 => ERROR

import * as api from "../api/jurnalumum.js";

export default {
    namespaced: true,
    state: {
        // state listing
        search_status: 0,
        current_page: 1,
        x_search: "",
        search_error_message: "",
        last_id: -1,
        jurnalumums: [],
        total_jurnalumum: 0,
        selected_jurnalumum: {},
        start_date: moment(new Date().getFullYear() + '-01-01').format('YYYY-MM-DD'),
        end_date: moment(new Date()).format('YYYY-MM-DD'),
        open_dialog_info: false,
        msg_info: "",
        drop_tipe_jurnal: [],
        x_drop_tipe_jurnal: {},

        // state jurnal umum
        user: one_user(),
        dialog_is_active: false,
        loading_jurnaltype: false,
        jurnaltypes: [],
        selected_jurnaltype: {},
        selected_jurnaltype_u: {},
        xdate: moment(new Date()).format('YYYY-MM-DD'),
        periodes: [],
        selected_periode: {},
        autocomplete_status: 0,
        dialogdetail: false,
        coalist: [],
        selected_coa: {},
        errors: [],
        errors_save_jurnal: [],
        xsummary: { "debit": 0, "credit": 0, "balance": 0 },
        open_dialog_detail_info: false,
        msg_detail_info: "",
        save_status: 0,
        save_error_message: "",
        alert_error: false,
        alert_success: false,
        msg_success: "",
        act: "",
        title: "",
        deskripsi: "",
        jurnaldetails: [],
        selected_jurnaldetail: {},
        periodeStartDate: moment(new Date()).format('YYYY-MM-DD'),
        periodeEndDate: moment(new Date()).format('YYYY-MM-DD'),
        is_posted: ""
    },
    mutations: {
        update_search_status(state, val) {
            state.search_status = val
        },
        update_current_page(state, val) {
            state.current_page = val
        },
        update_x_search(state, val) {
            state.x_search = val
            state.current_page = 1
        },
        update_search_error_message(state, val) {
            state.search_error_message = val
        },
        update_last_id(state, val) {
            state.last_id = val
        },
        update_jurnalumums(state, val) {
            state.jurnalumums = val
        },
        update_total_jurnalumum(state, val) {
            state.total_jurnalumum = val
        },
        update_selected_jurnalumum(state, val) {
            state.selected_jurnalumum = val
        },
        update_start_date(state, val) {
            state.start_date = val
        },
        update_end_date(state, val) {
            state.end_date = val
        },
        update_open_dialog_info(state, val) {
            state.open_dialog_info = val
        },
        update_msg_info(state, val) {
            state.msg_info = val
        },
        update_drop_tipe_jurnal(state, val) {
            state.drop_tipe_jurnal = val
        },
        update_x_drop_tipe_jurnal(state, val) {
            state.x_drop_tipe_jurnal = val
        },

        // state jurnal umum
        update_user(state, val) {
            state.user = val
        },
        update_dialog_is_active(state, val) {
            state.dialog_is_active = val
        },
        update_loading_jurnaltype(state, val) {
            state.loading_jurnaltype = val
        },
        update_jurnaltypes(state, val) {
            state.jurnaltypes = val
        },
        update_selected_jurnaltype(state, val) {
            state.selected_jurnaltype = val
        },
        update_selected_jurnaltype_u(state, val) {
            state.selected_jurnaltype_u = val
        },
        update_xdate(state, val) {
            state.xdate = val
        },
        update_periodes(state, val) {
            state.periodes = val
        },
        update_selected_periode(state, val) {
            state.selected_periode = val
        },
        update_autocomplete_status(state, val) {
            state.update_autocomplete_status = val
        },
        update_dialogdetail(state, val) {
            state.dialogdetail = val
        },
        update_coalist(state, val) {
            state.coalist = val
        },
        update_selected_coa(state, val) {
            state.selected_coa = val
        },
        update_errors(state, val) {
            state.errors = val
        },
        update_errors_save_jurnal(state, val) {
            state.errors_save_jurnal = val
        },
        update_xsummary(state, val) {
            state.xsummary = val
        },
        update_open_dialog_detail_info(state, val) {
            state.open_dialog_detail_info = val
        },
        update_msg_detail_info(state, val) {
            state.msg_detail_info = val
        },
        update_msg_detail_info(state, val) {
            state.msg_detail_info = val
        },
        update_save_status(state, val) {
            state.save_status = val
        },
        update_save_error_message(state, val) {
            state.save_error_message = val
        },
        update_alert_error(state, val) {
            state.alert_error = val
        },
        update_alert_success(state, val) {
            state.alert_success = val
        },
        update_msg_success(state, val) {
            state.msg_success = val
        },
        update_act(state, val) {
            state.act = val
        },
        update_title(state, val) {
            state.title = val
        },
        update_deskripsi(state, val) {
            state.deskripsi = val
        },
        update_jurnaldetails(state, val) {
            state.jurnaldetails = val
        },
        update_selected_jurnaldetail(state, val) {
            state.selected_jurnaldetail = val
        },
        update_periodeStartDate(state, val) {
            state.periodeStartDate = val
        },
        update_periodeEndDate(state, val) {
            state.periodeEndDate = val
        },
        update_is_posted(state, val) {
            state.is_posted = val
        }
    },
    actions: {
        async search(context, prm) {
            context.commit("update_search_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.search(prm)
                if (resp.status != "OK") {
                    context.commit("update_search_status", 3)
                    context.commit("update_search_error_message", resp.message)
                    context.commit("update_msg_info", resp.message)
                    context.commit("update_open_dialog_info", true)
                } else {
                    context.commit("update_search_status", 2)
                    context.commit("update_search_error_message", "")
                    context.commit("update_msg_info", resp.message)
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    context.commit("update_jurnalumums", data.records)
                    context.commit("update_total_jurnalumum", data.total)
                }
            } catch (e) {
                context.commit("update_search_status", 3)
                context.commit("update_search_error_message", e.message)
                context.commit("update_msg_info", e.message)
                context.commit("update_open_dialog_info", true)
            }
        },

        async openjurnaltype(context) {
            context.commit("update_loading_jurnaltype", true)
            try {
                let prm = {};
                prm.token = one_token()
                let resp = await api.getjurnaltype(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading_jurnaltype", false)
                } else {
                    context.commit("update_loading_jurnaltype", false)
                    context.commit("update_drop_tipe_jurnal", resp.data.records)
                }
            } catch (e) {
                context.commit("update_loading_jurnaltype", false)
            }
        },

        // jurnal umum
        async getjurnaltype(context) {
            context.commit("update_loading_jurnaltype", true)
            try {
                let prm = {};
                prm.token = one_token()
                let resp = await api.getjurnaltype(prm)
                if (resp.status != "OK") {
                    context.commit("update_loading_jurnaltype", false)
                } else {
                    context.commit("update_loading_jurnaltype", false)
                    context.commit("update_jurnaltypes", resp.data.records)
                    context.commit("update_selected_jurnaltype", resp.data.defaultju)
                    context.commit("update_selected_jurnaltype_u", resp.data.defaultju)
                }
            } catch (e) {
                context.commit("update_loading_jurnaltype", false)
            }
        },

        async getperiode(context, prm) {
            context.commit("update_autocomplete_status", 1)
            try {
                let resp = await api.getperiode(one_token(), prm)
                if (resp.status != "OK") {
                    context.commit("update_autocomplete_status", 3)
                    context.commit("update_search_error_message", resp.message)
                } else {
                    context.commit("update_autocomplete_status", 2)
                    context.commit("update_search_error_message", "")
                    let data = {
                        records: resp.data.records
                    }

                    context.commit("update_periodes", data.records)
                }
            } catch (e) {
                context.commit("update_autocomplete_status", 3)
                context.commit("update_search_error_message", e.message)
            }
        },

        async searchcoa(context, prm) {
            context.commit("update_autocomplete_status", 1)
            try {
                let resp = await api.searchcoa(one_token(), prm)
                if (resp.status != "OK") {
                    context.commit("update_autocomplete_status", 3)
                    context.commit("update_search_error_message", resp.message)
                } else {
                    context.commit("update_autocomplete_status", 2)
                    context.commit("update_search_error_message", "")
                    let data = {
                        records: resp.data.records
                    }

                    context.commit("update_coalist", data.records)
                }
            } catch (e) {
                context.commit("update_autocomplete_status", 3)
                context.commit("update_search_error_message", e.message)
            }
        },

        async savejurnalumum(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.savejurnalumum(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_error", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    let data = {
                        total: resp.data.total
                    }

                    if (data.total !== -1) {
                        context.commit("update_alert_success", true)
                        var msg = " Journal " + prm.title + " berhasil di ditambahkan"
                        context.commit("update_msg_success", msg)
                        context.dispatch("search", {
                            regionalid: context.state.user.S_RegionalID,
                            branchid: context.state.user.M_BranchID,
                            current_page: context.state.current_page,
                            search: context.state.x_search,
                            startdate: context.state.start_date,
                            enddate: context.state.end_date,
                            last_id: -1
                        })
                    } else {
                        context.commit("update_errors_save_jurnal", [])

                    }

                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                context.commit("update_alert_error", true)
            }
        },

        async editjurnalumum(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.editjurnalumum(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_error", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    let data = {
                        total: resp.data.total
                    }

                    if (data.total !== -1) {
                        context.commit("update_alert_success", true)
                        var msg = " Journal " + prm.title + " berhasil di diupdate"
                        context.commit("update_msg_success", msg)
                        context.dispatch("search", {
                            regionalid: context.state.user.S_RegionalID,
                            branchid: context.state.user.M_BranchID,
                            current_page: context.state.current_page,
                            search: context.state.x_search,
                            startdate: context.state.start_date,
                            enddate: context.state.end_date,
                            last_id: -1
                        })
                    } else {
                        context.commit("update_errors_save_jurnal", [])
                    }
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                context.commit("update_alert_error", true)
            }
        },

        async deletejurnalumum(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.deletejurnalumum(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_error", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    let data = {
                        total: resp.data.total
                    }

                    if (data.total !== -1) {
                        context.commit("update_alert_success", true)
                        var msg = " Journal " + prm.title + " berhasil dihapus"
                        context.commit("update_msg_success", msg)
                        context.dispatch("search", {
                            regionalid: context.state.user.S_RegionalID,
                            branchid: context.state.user.M_BranchID,
                            current_page: context.state.current_page,
                            search: context.state.x_search,
                            startdate: context.state.start_date,
                            enddate: context.state.end_date,
                            last_id: -1
                        })
                    } else {
                        context.commit("update_errors_save_jurnal", [])

                    }

                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                context.commit("update_alert_error", true)
            }
        },
    },
};
