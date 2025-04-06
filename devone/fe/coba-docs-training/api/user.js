const URL = "/one-api/mockup/masterdata/";

export async function save(prm) {
    try {
        var resp = await axios.post(URL + 'usergroup/addnewuser', prm);
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

export async function save_edit(prm) {
    try {
        var resp = await axios.post(URL + 'usergroup/edituser', prm);
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
        var resp = await axios.post(URL + 'usergroup/deleteuser', { id: id ,token:token});
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

export async function lookup(token,id) {
    try {
        var resp = await axios.post(URL + 'usergroup/lookupuser', { id: id, token:token });
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
