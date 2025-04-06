// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/usergroup.js"

export default {
    namespaced: true,
    state: {
        act:'new',
        lookup_usergroup: 0,
		 get_data_status:0,
        lookup_error_message: '',
        usergroups: [],
        total_usergroups: 0,
        total_filter_usergroups: 0,
        selected_usergroup: { name: "[ Belum memilih User Group ]" },
        save_status: 0,
        save_error_message: '',
        dialog_form_usergroup: false,
        dialog_edit_form_usergroup:false,
        alert_success: false,
        msg_success: "",
        error_clinic: false,
        error_name: false,
        error_dashboard: false,
        reports:[],
        selected_report:{},
        staffs:[],
        selected_staff:{},
        usergroupnames:[],
        selected_usergroupname:{},
        samplestations:[],
        selected_samplestation:{},
        dashboards:[],
        selected_dashboard:{},
        show_all:'N',
        errors:[]
    },
    mutations: {
        update_dashboards(state, val) {
            state.dashboards = val
        },
        update_selected_dashboard(state, val) {
            state.selected_dashboard = val
        },
        update_act(state, val) {
            state.act = val
        },
        update_error_code(state, val) {
            state.error_code = val
         },
        update_error_name(state, val) {
           state.error_name = val
        },
        update_error_clinic(state, val) {
           state.error_clinic = val
        },
        update_error_dashboard(state, val) {
           state.error_dashboard = val
        },
        update_errors(state, val) {
            state.errors = val
        },
        update_show_all(state, val) {
            state.show_all = val
        },
        update_lookup_error_message(state, status) {
            state.lookup_error_message = status
        },
        update_lookup_usergroup(state, status) {
            state.lookup_usergroup = status
        },
        update_usergroups(state, data) {
            state.usergroups = data.records
            state.total_usergroups = data.total
            state.total_filter_usergroups = data.total_filter
        },
        update_selected_usergroup(state, val) {
            state.selected_usergroup = val
        },
        update_save_status(state, val) {
            state.save_status = val
        },
        update_save_error_message(state, val) {
            state.save_error_message = val
        },
        update_dialog_form_usergroup(state, val) {
            state.dialog_form_usergroup = val
        },
        update_dialog_edit_form_usergroup(state, val) {
            state.dialog_edit_form_usergroup = val
        },
        update_alert_success(state, val) {
            state.alert_success = val
        },
        update_msg_success(state, val) {
            state.msg_success = val
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
        update_samplestations(state, val) {
            state.samplestations = val
        },
        update_selected_samplestation(state, val) {
            state.selected_samplestation = val
        },
        update_usergroupnames(state, val) {
            state.usergroupnames = val
        },
        update_selected_usergroupname(state, val) {
            state.selected_usergroupname = val
        },

		update_get_data_status(state, val) {
            state.get_data_status = val
        }

    },
    actions: {
        async lookup(context, prm) {
            context.commit("update_lookup_usergroup", 1)
            try {
                let resp = await api.lookup(one_token(), prm.search, prm.all)
                if (resp.status != "OK") {
                    context.commit("update_lookup_usergroup", 3)
                    context.commit("update_lookup_error_message", resp.message)
                } else {
                    context.commit("update_lookup_usergroup", 2)
                    context.commit("update_lookup_error_message", "")
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total,
                        total_filter: resp.data.total_filter
                    }
                    context.commit("update_usergroups", data)
                }
            } catch (e) {
                context.commit("update_lookup_usergroup", 3)
                context.commit("update_lookup_error_message", e.message)
            }
        },
        async getdashboards(context,prm) {
            context.commit("update_lookup_usergroup", 1)
            try {
                console.log("dasdsa")
                prm.token  = one_token()
                console.log(prm)
                let resp = await api.getdashboards(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_usergroup", 3)
                    context.commit("update_lookup_error_message", resp.message)
                } else {
                    context.commit("update_lookup_usergroup", 2)
                    context.commit("update_lookup_error_message", "")
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_dashboards", data.records)
                    if(prm.cat === 'new'){
                        if(data.records.length > 0)
                        context.commit("update_selected_dashboard", data.records[0])
                        else
                        context.commit("update_selected_dashboard", {})
                    }
                    else{
                        var xdata = data.records
                        var idx = _.findIndex(xdata, function(o) { return o.url == prm.dashboard })
                        context.commit("update_selected_dashboard", data.records[idx])
                    }
                    
                }
            } catch (e) {
                context.commit("update_lookup_usergroup", 3)
                context.commit("update_lookup_error_message", e.message)
            }
        },

        async save(context, prm) {
            context.commit("update_save_status", 1)
            context.commit("update_error_name", false)
            context.commit("update_error_dashboard", false)
            context.commit("update_error_clinic", false)
            try {
                prm.token = one_token()
                let resp = await api.save(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_save_error_message", resp.message)
                    var data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    if(data.total !== -1){
                        context.commit("update_errors",[])
                        context.commit("update_alert_success", true)
                        context.commit("update_dialog_form_usergroup", false)
                        var msg = "User Group " + prm.name + " sudah tersimpan dong ..."
                        context.commit("update_msg_success", msg)
                        context.dispatch("lookup", { search: "" , all:context.show_all})
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
        async update(context, prm) {
            context.commit("update_save_status", 1)
            context.commit("update_error_name", false)
            context.commit("update_error_dashboard", false)
            context.commit("update_error_clinic", false)
            try {
                prm.token = one_token()
                let resp = await api.update(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_save_error_message", resp.message)
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }

                    if(data.total !== -1){
                        context.commit("update_error_name", false)
                        context.commit("update_error_dashboard", false)
                        context.commit("update_error_clinic", false)

                        context.commit("update_alert_success", true)
                        context.commit("update_dialog_form_usergroup", false)
                        var msg = "User Group " + prm.name + " sudah terupdate dong ..."
                        context.commit("update_msg_success", msg)
                        context.dispatch("lookup", { search: "" , all:context.show_all})
                    }else{
                        if(resp.data.errorcode === 'Y'){
                            context.commit("update_error_code", true)
                        }
                        if(resp.data.errorname === 'Y'){
                            context.commit("update_error_name", true)
                        }
                    }

                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                console.log(e)
            }
        },
        async delete(context, prm) {
            context.commit("update_save_status", 1)
            try {
                let resp = await api.xdelete(one_token(),prm.usergroupid)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_alert_success", true)

                    var msg = "Schedule " + prm.usergroupname + " sudah dihapus dong"
                    context.commit("update_msg_success", msg)
                    context.commit("update_alert_success", true)
                    context.commit("update_selected_usergroup", {})
                    context.dispatch("lookup", { search: "" })
                    context.commit("user/update_usergroup_user", [], { root: true })
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                console.log(e)
            }
        },
		async getreportsample(context) {
            context.commit("update_get_data_status",1)
            try {
                let resp= await api.getreportsample(one_token())
                if (resp.status != "OK") {
                    context.commit("update_get_data_status",3)
                } else {
                    context.commit("update_get_data_status",2)
                    let data = {
                        records : resp.data.records,
                        total: resp.data.total
                    }
                      context.commit("update_staffs",data.records.staffs)
                    context.commit("update_reports",data.records.reports)
                    context.commit("update_samplestations",data.records.samplestations)
                    context.commit("update_usergroupnames",data.records.usergroupnames)
                }
            } catch(e) {
            context.commit("update_get_data_status",3)
            }
        }
    }
}
