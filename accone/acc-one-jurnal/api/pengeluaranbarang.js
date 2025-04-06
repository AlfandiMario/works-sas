const URL = "/one-api/mockup/masterdata/accounting/jurnalpengeluaranbarang/"

export async function get_periode(params) {
    try {
        var resp = await axios.post(URL + 'getperiode', params)
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.message
            }
        }

        let data = resp.data
        return data
    } catch (error) {
        return {
            status: "ERR",
            message: error.message
        }
    }
}

export async function get_coa(params) {
    try {
        var resp = await axios.post(URL + 'searchcoa', params)
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.message
            }
        }
        let data = resp.data
        return data
    } catch (error) {
        return {
            status: "ERR",
            message: error.message
        }
    }
}

export async function simpanjurnal(params) {
    try {
        var resp = await axios.post(URL + 'simpanjurnal', params)
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.message
            }
        }
        let data = resp.data
        return data
    } catch (error) {
        return {
            status: "ERR",
            message: error.message
        }
    }
}

export async function getdetailjurnal(params) {
    try {
        var resp = await axios.post(URL + 'getdetailjurnal', params)
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.message
            }
        }
        let data = resp.data
        return data
    } catch (error) {
        return {
            status: "ERR",
            message: error.message
        }
    }
}

export async function editdetailjurnal(params) {
    try {
        var resp = await axios.post(URL + 'editjurnal', params)
        if (resp.status != 200) {
            return {
                status: "ERR",
                message: resp.message
            }
        }
        let data = resp.data
        return data
    } catch (error) {
        return {
            status: "ERR",
            message: error.message
        }
    }
}