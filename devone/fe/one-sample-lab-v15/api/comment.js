const URL = "/one-api/mockup/samplinglab-v15/";
export async function save(prm) {
  try {
    var resp = await axios.post(URL + "comment/save", prm);
    if (resp.status != 200) {
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
export async function load(prm) {
  try {
    var resp = await axios.post(URL + "comment/load", prm);
    if (resp.status != 200) {
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
