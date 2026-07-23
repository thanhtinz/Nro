using UnityEngine.SceneManagement;

public class Management
{
    public static bool isLogo = true;

    public static string IpServer = "NRO GOD MOBI:116.118.47.179:14445:0,0,0";

    public static string LinkWeb = "@godmobi";

    // Chỉ còn 1 tab (đã bỏ Tab2/Game2)
    private static TabType[] tabTypes = new TabType[]
    {
        TabType.Tab1
    };

    private static string[] SceneNames = new string[]
    {
         "NROL"
    };
    public static TabType tab;

    private static bool SceneLoad(string sceneName)
    {
        for (int i = 0; i < SceneManager.sceneCount; i++)
        {
            if (SceneManager.GetSceneAt(i).name == sceneName)
            {
                return true;
            }
        }
        return false;
    }

    public static void ChangeTab(int index)
    {
        index = 0; // chỉ 1 tab
        tab = tabTypes[index];
        if (!SceneLoad(SceneNames[index]))
        {
            SceneManager.LoadScene(SceneNames[index], LoadSceneMode.Additive);
        }
    }
}
