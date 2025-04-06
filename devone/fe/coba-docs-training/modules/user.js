// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/user.js"

export default {
    namespaced: true,
    state: {
        users: [],
        save_status: 0,
        save_error_message: '',
        dialog_form_user: false,
        dialog_form_user_edit : false,
        lookup_user: 0,
        error_username: false,

        error_password: false,
        error_xreport: false,
        error_xusergroupname: false,
        error_xstaff: false,
        error_xsamplestation: false,
        error_iscoordinator: false,
        update_error_xusergroup: false,
        show_all:'N',
        reports:[],
        selected_report:{},
        staffs:[],
        selected_staff:{},
        samplestations:[],
        selected_samplestation:{},
        usergroupnames:[],
        selected_usergroupname:{},

        errors:[]
    },
    mutations: {
        update_errors(state, val) {
            state.errors = val
        },
        update_error_username(state, val) {
           state.error_username = val
        },
        update_error_password(state, val) {
           state.error_password = val
        },
        update_error_xreport(state, val) {
           state.error_xreport = val
        },
        update_error_xstaff(state, val) {
           state.error_xstaff = val
        },
        update_error_xusergroupname(state, val) {
           state.error_xusergroupname = val
        },
        update_error_xsamplestation(state, val) {
           state.error_xsamplestation = val
        },
        update_error_iscoordinator(state, val) {
           state.error_iscoordinator = val
        },
        update_error_xusergroup(state, val) {
           state.error_xusergroup = val
        },
        update_users(state, data) {
            state.users = data
        },
        update_save_status(state, val) {
            state.save_status = val
        },
        update_save_error_message(state, val) {
            state.save_error_message = val
        },
        update_dialog_form_user(state, val) {
            state.dialog_form_user = val
        },
        update_dialog_form_user_edit(state, val) {
            state.dialog_form_user_edit = val
        },
        update_reports(state, val) {
            state.reports = val
        },
        update_selected_report(state, val) {
            state.selected_report = val
        },

        update_staffs(state, val) {
            state.staffs = val
        },
        update_selected_staff(state, val) {
            state.selected_staff = val
        },
        update_usergroupnames(state, val) {
            state.usergroupnames = val
        },
        update_selected_usergroupname(state, val) {
            state.selected_usergroupname = val
        },
        update_samplestations(state, val) {
            state.samplestations = val
        },
        update_selected_samplestation(state, val) {
            state.selected_samplestation = val
        },
        update_lookup_user(state, val) {
            state.lookup_user = val
        }
    },
    actions: {
        async save_edit(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.save_edit(prm)
                if (resp.status != "OK") {
                    context.commit("usergroup/update_save_status", 3, { root: true })
                    context.commit("usergroup/update_save_error_message", resp.message, { root: true })
                } else {
                    var data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    if(data.total !== -1){
                        context.commit("usergroup/update_save_status", 2, { root: true })
                        context.commit("usergroup/update_save_error_message", resp.message, { root: true })
                        context.commit("usergroup/update_alert_success", true, { root: true })

                        context.commit("update_dialog_form_user_edit", false)
                        var msg = "User " + prm.username + " sudah update dong"
                        context.commit("usergroup/update_msg_success", msg, { root: true })
                        context.commit("usergroup/update_alert_success", true, { root: true })
                        context.dispatch("lookup", {
                            id: prm.usergroupid
                        })
                    }else{
                        context.commit("update_errors", resp.data.errors)
                    }
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                console.log(e)
            }
        },
        async save(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.save(prm)
                if (resp.status != "OK") {
                    context.commit("usergroup/update_save_status", 3, { root: true })
                    context.commit("usergroup/update_save_error_message", resp.message, { root: true })
                } else {
                    var data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    if(data.total !== -1){
                        context.commit("usergroup/update_save_status", 2, { root: true })
                        context.commit("usergroup/update_save_error_message", resp.message, { root: true })
                        context.commit("usergroup/update_alert_success", true, { root: true })

                        context.commit("update_dialog_form_user", false)
                        var msg = "User " + prm.username + " sudah update dong"
                        context.commit("usergroup/update_msg_success", msg, { root: true })
                        context.commit("usergroup/update_alert_success", true, { root: true })
                        context.dispatch("lookup", {
                            id: prm.usergroupid
                        })
                    }else{
                        context.commit("update_errors", resp.data.errors)
                    }
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                console.log(e)
            }
        },
        async lookup(context, prm) {
            context.commit("update_lookup_user", 1)
            try {
                let resp = await api.lookup(one_token(),prm.id)
                if (resp.status != "OK") {
                    context.commit("update_lookup_user", 3)
                } else {
                    context.commit("update_lookup_user", 2)
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_users", data.records)
                }
            } catch (e) {
                context.commit("update_lookup_user", 3)
            }
        },
        async delete(context, prm) {
            context.commit("update_save_status", 1)
            try {
                let resp = await api.xdelete(one_token(),prm.xid)
                if (resp.status != "OK") {
                    context.commit("usergroup/update_save_status", 3, { root: true })
                    context.commit("usergroup/update_save_error_message", resp.message, { root: true })
                } else {
                    context.commit("usergroup/update_save_status", 2, { root: true })
                    context.commit("usergroup/update_save_error_message", resp.message, { root: true })
                    context.commit("usergroup/update_alert_success", true, { root: true })

                    //context.commit("update_dialog_form_schedule_promise", false)
                    var msg = "User "+prm.username+" dari usergroup " + prm.usergroupname + " sudah dihapus dong"
                    context.commit("usergroup/update_msg_success", msg, { root: true })
                    context.commit("usergroup/update_alert_success", true, { root: true })
                    context.dispatch("lookup", {
                        id: prm.usergroupid
                    })
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                console.log(e)
            }
        }
    }
}
