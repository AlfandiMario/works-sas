const URL = "/one-api/mockup/sampling-all-cpone/";

export async function search(prm) {
  try {
    var resp = await axios.post(URL + "order/info", prm);
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
