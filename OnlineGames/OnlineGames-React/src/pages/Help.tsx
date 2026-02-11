import { useNavigate } from "react-router-dom";
import Button from "../components/ui/Button";
import "../styles/help.css";

const Help = () => {
  const navigate = useNavigate();

  return (
    <div className="helpLayout">
      <h1 className="helpTitle">❓ Help</h1>

      <div className="helpCard">
        <h2>🎯 What is Mini Games?</h2>
        <p>
          Mini Games is a collection of small interactive games designed to
          help you learn and have fun at the same time.
        </p>
      </div>

      <div className="helpCard">
        <h2>🧠 Quizzes</h2>
        <p>
          Test your knowledge with different types of quizzes. Each quiz gives
          instant feedback and a final score.
        </p>
      </div>

      <div className="helpCard">
        <h2>🚧 Coming Soon</h2>
        <p>
          New games and features are under development. Stay tuned!
        </p>
      </div>

      <Button onClick={() => navigate("/")}>
        ← Back to Home
      </Button>
    </div>
  );
};

export default Help;
