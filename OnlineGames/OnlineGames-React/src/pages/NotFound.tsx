import { useNavigate } from "react-router-dom";
import Button from "../components/ui/Button";

const NotFound = () => {
  const navigate = useNavigate();

  return (
    <div style={{ textAlign: "center", padding: "4rem" }}>
      <h1>404</h1>
      <p>Az oldal nem található 😕</p>

      <Button onClick={() => navigate("/")}>
        Vissza a főoldalra
      </Button>
    </div>
  );
};

export default NotFound;
