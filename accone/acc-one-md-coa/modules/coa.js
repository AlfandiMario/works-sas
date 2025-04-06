// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/coa.js"

export default {
    namespaced: true,
    state: {
        search_status: 0,
        current_page: 1,
        x_search: "",
        search_error_message: "",
        last_id: -1,
        coas: [],
        total_coas: 0,
        selected_coa: {},
        dialog_form_coa: false,
        act: 'new',
        save_status: 0,
        save_error_message: '',
        errors: [],
        alert_success: false,
        msg_success: "",
        alert_error: false,
        dialog_error: false,
        open_print: false
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
        update_coas(state, val) {
            state.coas = val
        },
        update_total_coas(state, val) {
            state.total_coas = val
        },
        update_selected_coa(state, val) {
            state.selected_coa = val
        },
        update_dialog_form_coa(state, val) {
            state.dialog_form_coa = val
        },
        update_act(state, val) {
            state.act = val
        },
        update_save_status(state, val) {
            state.save_status = val
        },
        update_save_error_message(state, val) {
            state.save_error_message = val
        },
        update_errors(state, val) {
            state.errors = val
        },
        update_alert_success(state, val) {
            state.alert_success = val
        },
        update_msg_success(state, val) {
            state.msg_success = val
        },
        update_alert_error(state, val) {
            state.alert_error = val
        },
        update_dialog_error(state, val) {
            state.dialog_error = val
        },
        update_open_print(state, value) {
            state.open_print = value
        }
    },
    actions: {
        async search(context) {
            context.commit("update_search_status", 1)
            try {
                var prm = {
                    token: one_token(),
                    current_page: context.state.current_page,
                    search: context.state.x_search,
                    last_id: -1
                }
                let resp = await api.search(prm)
                if (resp.status != "OK") {
                    context.commit("update_search_status", 3)
                    context.commit("update_search_error_message", resp.message)
                } else {
                    context.commit("update_search_status", 2)
                    context.commit("update_search_error_message", "")
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    context.commit("update_coas", data.records)
                    context.commit("update_total_coas", data.total)

                }
            } catch (e) {
                context.commit("update_search_status", 3)
                context.commit("update_search_error_message", e.message)
            }
        },
        async save(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.save(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_error", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    var data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    if (data.total !== -1) {
                        context.commit("update_errors", [])
                        context.commit("update_alert_success", true)
                        context.commit("update_dialog_form_coa", false)
                        var msg =" Account " + prm.description + " sudah tersimpan dong ..."
                        context.commit("update_msg_success", msg)         
                        context.dispatch("search")               
                    } else {
                        context.commit("update_errors", resp.data.errors)

                    }

                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                context.commit("update_alert_error", true)
                console.log(e)
            }
        },
        async update(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.update(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_error", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    if (data.total !== -1) {
                        context.commit("update_alert_success", true)
                        context.commit("update_dialog_form_coa", false)
                        var msg = " Account " + prm.description + " sudah terupdate dong ..."
                        context.commit("update_msg_success", msg)
                        context.dispatch("search")  
                    }else {
                        context.commit("update_errors", resp.data.errors)

                    }

                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                context.commit("update_alert_error", true)
                console.log(e)
            }
        },

        async delete(context, prm) {
            context.commit("update_save_status", 1)
            try {
                let resp = await api.xdelete(one_token(), prm.coaid)
                console.log(prm.coid)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_error", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_success", true)

                    var msg = " Account " + prm.description + " sudah dihapus dong"
                    context.commit("update_msg_success", msg)
                    context.commit("update_alert_success", true)
                    context.commit("update_selected_coa", {})
                    context.dispatch("search") 
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_alert_error", true)
                console.log(e)
            }
        },
    }
}