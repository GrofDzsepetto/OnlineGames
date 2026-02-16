import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import Button from "../components/ui/Button";
import "../styles/help.css";

const Help = () => {
  const navigate = useNavigate();
  const { t } = useTranslation();

  return (
    <div className="helpLayout">
      <h1 className="helpTitle">❓ {t("help.title")}</h1>

      <div className="helpCard">
        <h2>🎯 {t("help.whatIsTitle")}</h2>
        <p>{t("help.whatIsText")}</p>
      </div>

      <div className="helpCard">
        <h2>🧠 {t("help.quizTitle")}</h2>
        <p>{t("help.quizText")}</p>
      </div>

      <div className="helpCard">
        <h2>🚧 {t("help.comingTitle")}</h2>
        <p>{t("help.comingText")}</p>
      </div>

      <div className="helpCard">
        <h2>⚙️ {t("help.openSourceTitle")}</h2>
        <p>
          {t("help.openSourceText")}{" "}
          <a
            href="https://github.com/Dzsepetto/OnlineGames"
            target="_blank"
            rel="noopener noreferrer"
          >
            {t("help.github")}
          </a>
        </p>
      </div>

      <Button onClick={() => navigate("/")}>
        ← {t("help.back")}
      </Button>
    </div>
  );
};

export default Help;
