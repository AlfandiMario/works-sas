const URL = "/one-api/mockup/masterdata/accounting/";

export async function search(prm) {
  try {
    var resp = await axios.post(URL + "jurnalumum/search", prm);
    if (resp.status !== 200) {
      return {
        status: "ERR",
        message: resp.statusText,
      };
    }

    let data = resp.data;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      message: e.message,
    };
  }
}

export async function getjurnaltype(prm) {
    try {
        var resp = await axios.post(URL + 'jurnalumum/getjurnaltype', prm);
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

export async function getperiode(token, prm) {
    try {
        var resp = await axios.post(URL + 'jurnalumum/getperiode', {
            token: token,
            search: prm
        });
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

export async function searchcoa(token, prm) {
    try {
        var resp = await axios.post(URL + 'jurnalumum/searchcoa', {
            token: token,
            search: prm
        });
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

export async function savejurnalumum(prm) {
    try {
        var resp = await axios.post(URL + 'jurnalumum/savejurnalumum', prm);
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