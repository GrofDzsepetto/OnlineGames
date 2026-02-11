import { useTranslation } from "react-i18next";
import { useNavigate } from "react-router-dom";
import Button from "../components/ui/Button";
import "../styles/home.css";


const Home = () => {
  const {t} = useTranslation();
  const navigate = useNavigate();

  return (
    <div className="homeLayout">
      <h1 className="homeTitle">{t("home.title")}</h1>

      <div className="homeGrid">
        <Button className="homeCard" onClick={() => navigate("/quizzes")}>
          {t("home.quizzes")}
        </Button>

        <Button className="homeCard disabled">
          {t("home.comingSoon")}
        </Button>

        <Button className="homeCard disabled">
          {t("home.comingSoon")}
        </Button>
      </div>

      {/* Floating help button */}
      <button
        className="helpButton"
        onClick={() => navigate("/help")}
        aria-label="Help"
      >
        ?
      </button>
    </div>
  );
};

export default Home;
