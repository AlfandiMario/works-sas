// https://accone.aplikasi.web.id/one-api/mockup/masterdata/accounting/Jurnalgaji/searchCoa
const URL = "/one-api/mockup/masterdata/accounting/Jurnalgaji";

export async function getJurnalType(prm) {
    try {
        var resp = await axios.post(URL + '/getJurnalType', prm);
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
export async function searchPeriode(prm) {
    try {
        var resp = await axios.post(URL + '/searchPeriode', prm);
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
export async function searchCoa(prm) {
    try {
        var resp = await axios.post(URL + '/searchCoa', prm);
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
export async function searchCoaDetail(prm) {
    try {
        var resp = await axios.post(URL + '/searchCoaDetail', prm);
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
export async function searchBranchPercent(prm) {
    try {
        var resp = await axios.post(URL + '/searchBranchPercent', prm);
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
export async function getDefaultBranch(prm) {
    try {
        var resp = await axios.post(URL + '/getBranch', prm);
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
export async function saveJurnalGaji(prm) {
    try {
        var resp = await axios.post(URL + '/saveJurnalGaji', prm);
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
export async function getDetail(prm) {
    try {
        var resp = await axios.post(URL + '/getDetail', prm);
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