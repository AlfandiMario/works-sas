const URL = "/one-api/mockup/masterdata/";

export async function lookup(token, search,all ) {
    try {
        var resp = await axios.post(URL + 'usergroupv2/lookup', { token: token, search: search, all:all });
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}

export async function getdashboards(prm) {
    try {
        var resp = await axios.post(URL + 'usergroupv2/getdashboards', prm);
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}



export async function getreportsample(token) {
    try {
        var resp = await axios.post(URL + 'usergroupv2/getreportsample',{token:token});
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}

export async function save(prm) {
    try {
        var resp = await axios.post(URL + 'usergroupv2/addnewusergroup', prm);
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}

export async function update(prm) {
    try {
        var resp = await axios.post(URL + 'usergroupv2/editusergroup', prm);
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}


export async function xdelete(token,id) {
    try {
        var resp = await axios.post(URL + 'usergroupv2/deleteusergroup', { id: id, token:token });
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.statusText
            };
        }
        let data = resp.data;
        return data;
    } catch (e) {
        return {
            status: "ERR",
            message: e.message
        };
    }
}
