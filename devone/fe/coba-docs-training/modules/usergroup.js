import * as api from "../api/usergroup.js"

export default {
    namespaced: true,
    state: {
        usergroups: [],
        dialogusergroup: false,
        usergroupname: "",
        valid: true,
    },
    mutations: {
        update_dialogusergroup(state, value) {
            state.dialogusergroup = value;
        },
        update_usergroupname(state, value) {
            state.usergroupname = value;
        },
        update_usergroups(state, data) {
            state.usergroups = data.records
            state.total_usergroups = data.total
            state.total_filter_usergroups = data.total_filter
        },
    },
    actions: {
        async lookup(context, prm) {
            try {
                let resp = await api.lookup(one_token(), prm.search, prm.all)
                if (resp.status != "OK") {
                    console.log("lookup error", resp)
                } else {
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total,
                        total_filter: resp.data.total_filter
                    }
                    context.commit("update_usergroups", data)
                    console.log("lookup", data)
                }
            } catch (e) {
                console.log("lookup error", e.message)
            }
        },
    },
}