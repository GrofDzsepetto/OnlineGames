import { NavLink } from "react-router-dom";
import { useAuth } from "../../auth/AuthContext";

const Navbar = () => {
  const { user } = useAuth();

  return (
    <nav style={{ display: "flex", justifyContent: "space-between" }}>
      <div>
        <NavLink to="/">Home</NavLink>{" "}
        <NavLink to="/quizzes">Quiz</NavLink>
      </div>

      <div>
        {user ? (
          <strong>{user.name || user.email}</strong>
        ) : (
          <NavLink to="/login">Login</NavLink>
        )}
      </div>
    </nav>
  );
};

export default Navbar;
