// API :
// search bank
// paramater : query , page , rowPerPage
const URL =
  "http://lebaran.aplikasi.web.id/smartlab_api/vuex/t02/search_bank";

export async function searchBank(query, page, rowPerPage = 15) {
  try {
    var resp = await axios.post(URL, {
      query: query,
      page: page,
      rowPerPage: rowPerPage
    });
    if (resp.status != 200) {
      return {
        status: "ERR",
        query: query,
        message: resp.statusText
      };
    }
    let data = resp.data;
    data.query = query;
    return data;
  } catch (e) {
    return {
      status: "ERR",
      query: query,
      message: e.message
    };
  }
}
